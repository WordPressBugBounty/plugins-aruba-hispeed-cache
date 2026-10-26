<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Purge a product page when its stock changes.
 *
 * Stock is the one thing an order changes that a visitor can see: the stock count and the
 * in-stock / out-of-stock line on the product page, and whatever the shop archive renders
 * from them. Neither purge path in AHSC_PostType.php notices, because WooCommerce does not
 * update the product *post* when stock moves. WC_Product_Data_Store_CPT::update() only
 * calls wp_update_post() when a post-table field changed; for a meta-only change it writes
 * post_modified with a raw $wpdb->update and calls clean_post_cache(). So no post_updated,
 * no transition_post_status, and nothing for those hooks to act on.
 *
 * This used to be covered by accident. On legacy order storage the *order* was a post, so
 * placing one fired post_updated for the order and queued a full-site purge that dropped
 * the product page along with everything else. Under HPOS — the default since WC 8.2 —
 * orders stopped being posts and that quietly stopped happening. Listening to the stock
 * event directly replaces both the accident and the collateral damage.
 *
 * Deliberately a *targeted* purge, never purgeAll: a busy store would otherwise drop its
 * whole proxy cache on every checkout. That is also why this is not gated the way the
 * whole-cache toggles are — stock is not one of the events the settings screen enumerates.
 *
 * php version 7.4
 *
 * @category Wordpress-plugin
 * @package  Aruba-HiSpeed-Cache
 * @author   Aruba Developer <hispeedcache.developer@aruba.it>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 */

if ( is_array( AHSC_CONSTANT[ 'ARUBA_HISPEED_CACHE_OPTIONS' ] ) &&
	AHSC_CONSTANT[ 'ARUBA_HISPEED_CACHE_OPTIONS' ][ 'ahsc_purge_homepage_on_edit' ] ) {

	// Quantity changed: both hooks hand over the product (or variation) object itself.
	\add_action( 'woocommerce_product_set_stock', 'ahsc_queue_product_purge', 200, 1 );
	\add_action( 'woocommerce_variation_set_stock', 'ahsc_queue_product_purge', 200, 1 );

	// In stock / out of stock changed: these pass ( $product_id, $stock_status, $product ).
	\add_action( 'woocommerce_product_set_stock_status', 'ahsc_queue_product_purge_by_id', 200, 3 );
	\add_action( 'woocommerce_variation_set_stock_status', 'ahsc_queue_product_purge_by_id', 200, 3 );
}

/**
 * Queue the product behind a stock event.
 *
 * @param object $product WC_Product or WC_Product_Variation.
 * @return void
 */
function ahsc_queue_product_purge( $product ) {
	if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
		return;
	}

	$ahsc_id = (int) $product->get_id();

	/*
	 * A variation has no URL of its own; what a visitor sees is the parent product page.
	 */
	if ( method_exists( $product, 'get_parent_id' ) ) {
		$ahsc_parent = (int) $product->get_parent_id();

		if ( $ahsc_parent > 0 ) {
			$ahsc_id = $ahsc_parent;
		}
	}

	ahsc_queue_product_purge_id( $ahsc_id );
}

/**
 * Queue the product behind a stock *status* event, which passes an id rather than an object.
 *
 * @param int    $product_id   Product or variation id.
 * @param string $stock_status Unused; the new stock status.
 * @param object $product      The product object, when WooCommerce supplies one.
 * @return void
 */
function ahsc_queue_product_purge_by_id( $product_id, $stock_status = '', $product = null ) {
	if ( is_object( $product ) ) {
		ahsc_queue_product_purge( $product );

		return;
	}

	ahsc_queue_product_purge_id( (int) $product_id );
}

/**
 * Add one product id to the queue, and arrange for it to be flushed.
 *
 * Collected rather than purged on the spot because a single checkout moves stock on every
 * line item, and an importer or a bulk stock update moves it on hundreds. One purgeUrls()
 * at the end of the request sends each URL once.
 *
 * @param int $product_id Product id.
 * @return void
 */
function ahsc_queue_product_purge_id( $product_id ) {
	global $ahsc_product_purge_queue;

	if ( $product_id <= 0 ) {
		return;
	}

	if ( ! is_array( $ahsc_product_purge_queue ) ) {
		$ahsc_product_purge_queue = array();
	}

	if ( isset( $ahsc_product_purge_queue[ $product_id ] ) ) {
		return;
	}

	$ahsc_product_purge_queue[ $product_id ] = true;

	if ( 1 === count( $ahsc_product_purge_queue ) ) {
		\add_action( 'shutdown', 'ahsc_purge_queued_products', 90 );
	}
}

/**
 * Purge every product page queued during this request.
 *
 * @return void
 */
function ahsc_purge_queued_products() {
	global $ahsc_product_purge_queue;

	if ( empty( $ahsc_product_purge_queue ) ) {
		return;
	}

	$ahsc_targets = array();

	foreach ( array_keys( $ahsc_product_purge_queue ) as $ahsc_id ) {
		/*
		 * Skip anything not publicly published. get_permalink() runs the status through
		 * wp_force_plain_post_permalink(), so a draft product would come back as ?p=123 —
		 * not a URL the proxy ever cached.
		 */
		if ( 'publish' !== \get_post_status( $ahsc_id ) ) {
			continue;
		}

		$ahsc_link = \get_permalink( $ahsc_id );

		if ( $ahsc_link ) {
			$ahsc_targets[] = $ahsc_link;
		}
	}

	$ahsc_product_purge_queue = array();

	if ( empty( $ahsc_targets ) ) {
		return;
	}

	$cleaner = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	$cleaner->setPurger( AHSC_PURGER );

	if ( class_exists( 'ArubaSPA\HiSpeedCache\Debug\Logger' ) ) {
		// Logger.
		AHSC_log( 'hook::stock::' . implode( ', ', $ahsc_targets ), __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		// Logger.
	}

	$cleaner->purgeUrls( $ahsc_targets );
}
