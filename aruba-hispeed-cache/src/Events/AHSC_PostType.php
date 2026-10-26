<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $pagenow;
/* $ahsc_allowed_cases used to live here; ahsc_status_change_may_purge() replaced it. */
$ahsc_target=array();
$ahsc_is_json=(defined('REST_REQUEST') && REST_REQUEST) || true === wp_is_json_request() ||
         (isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), trailingslashit( rest_get_url_prefix() ) ) !== false)  ;
/*
 * Read-only routing check on WooCommerce's own AJAX parameter: it only decides whether the
 * purge hooks get registered, nothing is written and no form is processed, so there is no
 * nonce to verify — WooCommerce owns that request, not this plugin.
 */
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing check only, see comment above.
$ahsc_wc_ajax = isset( $_REQUEST['wc-ajax'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wc-ajax'] ) ) : '';

/*
 * Do not register the post purge hooks during a WooCommerce checkout, or on the nav-menus
 * screen.
 *
 * The checkout half never worked. The condition read
 *
 *     if ( $is_ajax && ( '' !== $wc_ajax && 'checkout' !== $wc_ajax ) || 'nav-menus.php' !== $pagenow )
 *
 * and && binds tighter than ||, so PHP evaluated it as ( A && B ) || C with C being
 * 'nav-menus.php' !== $pagenow — true in essentially every request. C alone therefore
 * decided the outcome, the WooCommerce clause could never exclude anything, and a
 * ?wc-ajax=checkout request registered the hooks like any other. Regrouped so both
 * exclusions apply, which is what the original was reaching for.
 */
$ahsc_is_ajax = ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || defined( 'DOING_AJAX' );

if ( 'nav-menus.php' !== $pagenow && ! ( $ahsc_is_ajax && 'checkout' === $ahsc_wc_ajax ) ) {
	if(is_array(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit']   /*||
	 AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_del'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_mod'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_del']*/
	){
		\add_action( 'post_updated',  'ahsc_post_updated' , 20, 3 );

		if ( did_action( 'elementor/loaded' ) ) {
			add_action( 'elementor/editor/after_save', 'ahsc_post_updated', 10 , 2 );
		}
	}
}

if('nav-menus.php' !== $pagenow){
	if(is_array(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit'] /*||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_del'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_mod'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_del']*/
	){

		//if ( ahsc_has_transient( 'ahsc_is_purged' ) ) {
			\add_action( 'transition_post_status', 'ahsc_transition_post_status' , 20, 3 );
			\add_action( 'pre_post_update',  'ahsc_get_terms_target' , 20, 1 );
		//}

	}
}
/**
 * Fires after a post type has been updated, and the term cache has been cleaned.
 *
 * @see https://developer.wordpress.org/reference/hooks/post_updated/
 * or
 * @see https://github.com/WordPress/WordPress/blob/master/wp-includes/post.php
 *
 * param int     $post_ID Post ID.
 * param WP_Post $post_after Post object following the update.
 * param WP_Post $post_before Post object before the update.
 *
 * @return void
 */
 function ahsc_post_updated($post_ID, $data = null, $post_before = null) {


	/*
	 * Status filter, shared with ahsc_transition_post_status() — see
	 * ahsc_status_change_may_purge() for what it tests and why.
	 *
	 * Without any filter here this handler fired on *every* update of a listed type, and a
	 * draft autosave is an update: wp_update_post() on the draft itself, so post_updated
	 * fired and a full purge was queued. Nothing sets the ahsc_is_purged lock on this path,
	 * so an open draft dropped the whole proxy cache once per autosave interval. Confirmed
	 * on a live 3.1.0 install.
	 *
	 * Read the new status back from the post rather than from $data: on the elementor
	 * editor/after_save hook this same callback receives the editor payload there, not a
	 * WP_Post. get_post_status() is a warm cache read at this point either way. $post_before
	 * is null on that hook too, so the old status simply comes out empty there.
	 */
	$ahsc_status_now = \get_post_status( $post_ID );
	$ahsc_status_was = ( $post_before instanceof WP_Post ) ? $post_before->post_status : '';

	if ( ! ahsc_status_change_may_purge( $ahsc_status_now, $ahsc_status_was ) ) {
		return;
	}

	if ( ahsc_post_type_may_purge( get_post_type( $post_ID ) ) ) {
		 /**
		  * I check whether the cleaning transient "ahsc_do_purge_deferred" is present in case I inhibit this action.
		  */
		$do_purge = ahsc_has_transient( 'ahsc_do_purge_deferred' );

		if ( $do_purge ) {
			return;
		}
		/*
		 * The ahsc_is_purged lock used to cause a return here. It no longer does: this handler
		 * only *queues* a purge, and dropping the queue entry is how an edit made shortly after
		 * another one ended up never purging at all. The ahsc_do_purge_deferred check above
		 * already collapses repeat events into a single purge, which is all the throttling this
		 * path needs.
		 */

		 //$cleaner =new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
		 //$cleaner->setPurger( AHSC_PURGER );

		/**
		 * If home page cleaning is set, there is no point in going any further, the entire site cache will be cleaned.
		 */

		/*if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')){
			// Logger.
			AHSC_log( 'hook::post_updated::home', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}*/
		//$cleaner->purgeAll();
		 ahsc_set_transient( 'ahsc_do_purge_deferred', \time(), MINUTE_IN_SECONDS );
	}
}

/**
 * Fires after a post type has been updated, and the term cache has been cleaned.
 *
 * @see https://developer.wordpress.org/reference/hooks/transition_post_status/
 *
 * @param int|string $new_status new status.
 * @param int|string $old_status old status.
 * @param \WP_Post   $post       Post object.
 *
 * @return void
 */
 function ahsc_transition_post_status( $new_status, $old_status, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
global $ahsc_is_json,$ahsc_target;
	/*
	 * Recently purged: queue the work instead of dropping it.
	 *
	 * This used to return outright, so publishing one post and then another eight seconds
	 * later left the second one stale in the proxy until its TTL expired. Rate limiting is a
	 * reasonable goal; losing the purge is not. The deferred flag is consumed on shutdown of
	 * this same request (see AHSC_Deferer.php), so in practice this now coalesces repeated
	 * events *within* a request — a bulk edit, an importer — into one purgeAll at the end of
	 * it, rather than suppressing purges across requests.
	 */
	if (  ahsc_has_transient( 'ahsc_is_purged' ) ) {
		if ( ! ahsc_has_transient( 'ahsc_do_purge_deferred' ) ) {
			ahsc_set_transient( 'ahsc_do_purge_deferred', \time(), MINUTE_IN_SECONDS );
		}

		return;
	}

	/*
	 * Same post type rule as ahsc_post_updated() applies. This path had none at all, which is
	 * what made the old allow-list only half effective: a type excluded there still purged the
	 * whole site through here the moment it published or trashed anything — which is precisely
	 * what queue and logging CPTs do all day.
	 */
	if ( ! ahsc_post_type_may_purge( $post->post_type ) ) {
		return;
	}

	/**
	 * Disable the purge action on auto save.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_is_post_autosave/
	 */
	if ( \wp_is_post_autosave( $post ) ) {
		return;
	}

	/*
	 * Same status rule as ahsc_post_updated() applies. This path used to test only the new
	 * status against publish/future/trash, which is what left unpublishing uncovered here:
	 * publish to draft arrives as 'draft' and was dropped.
	 */
	if ( ! ahsc_status_change_may_purge( $new_status, $old_status ) ) {
		return;
	}

	/**
	 * For json call
	 */
	if ( $ahsc_is_json ) {
		$do_purge             = ahsc_has_transient( 'ahsc_do_purge_deferred' );
		$do_purge_log_message = 'Transint presente';

		if ( ! $do_purge ) {
			$do_purge_log_message = 'Transint non presente presente lo imposto';
			ahsc_set_transient( 'ahsc_do_purge_deferred', \time(), MINUTE_IN_SECONDS );
		}
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')){
		 AHSC_log( $do_purge_log_message, 'deferred', 'info' );
		}
		return;
	}

	$cleaner       = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	//$plugin_option = $this->container->get_service( 'ahsc_get_option' );

	$options = array(
		'log_function'         => __FUNCTION__,
		'log_option'           => 'ahsc_purge',
		'is_publish_or_future' => \in_array( $new_status, array( 'publish', 'future' ), true ),
		'is_trashed'           => 'trash' === $new_status,
		'was_published'        => 'publish' === $old_status,
		'post'                 => $post,
	);

	/**
	 * If home page cleaning is set, there is no point in going any further, the entire site cache will be cleaned.
	 */
	/*
	 * was_published catches unpublishing, and it has to be handled *here* rather than by the
	 * targeted branch further down. get_permalink() calls wp_force_plain_post_permalink(),
	 * which returns true for any status that is not viewable, so once a post is back to
	 * draft its permalink comes back as ?p=123 — not the pretty URL the proxy actually
	 * cached. Purging that would purge nothing. The whole-cache purge is also what the
	 * settings label promises for content that is "added, edited or deleted".
	 */
	if ( ( true === $options['is_publish_or_future'] || true === $options['was_published'] ) &&
	     AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit'] ) {
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')){
		// Logger.
		AHSC_log( 'hook::transition_post_status::home::' . (string) $options['log_option'], __NAMESPACE__ . '::' . (string) $options['log_function'], 'debug' );
		// Logger.
		}
		$cleaner->purgeAll();
		ahsc_set_transient( 'ahsc_is_purged', \time(), MINUTE_IN_SECONDS );
		return;
	}

	if ( true === $options['is_trashed'] && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_del']  ) {
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')){
		// Logger.
		AHSC_log( 'hook::transition_post_status::home::ahsc_purge_archive_on_del', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		// Logger.
		}
		$cleaner->purgeAll();

		ahsc_set_transient( 'ahsc_is_purged', \time(), MINUTE_IN_SECONDS );
		return;
	}

	/**
	 * Edit item
	 */
	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_mod']  ) {
		$options['log_option'] = $options['log_option'] . '_page_on_mod';
		$options['target']     = \get_permalink( $post->ID );

		ahsc_post_mod_cache_cleaner( $options );
		return;
	}

	$taxonomies = \get_object_taxonomies( $post->post_type );
	if ( empty( $taxonomies ) ) {
		return;
	}

	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit'] && true === $options['is_publish_or_future'] ) {
		$options['log_option'] = $options['log_option'] . '_archive_on_edit';
		$options['target']     = $ahsc_target;

		ahsc_post_mod_cache_cleaner( $options );
		return;
	}

	/**
	 * Delete items
	 */
	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_del'] && true === $options['is_trashed'] ) {
		$options['log_option'] = $options['log_option'] . '_archive_on_del';
		$options['target']     = $ahsc_target;

		ahsc_post_mod_cache_cleaner( $options );
		return;
	}
}

/**
 * Proxy cache cleaner based on passed options.
 *
 * @param  array $arg .
 * @return void
 */
 function ahsc_post_mod_cache_cleaner( $arg ) {
	 $cleaner       = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	$ahsc_target  = $arg['target'];

	if ( ! \is_array( $ahsc_target ) ) {

		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')){
		// Logger.
		AHSC_log(
			'hook::transition_post_status::' . (string) $arg['log_option'] . '::' . $ahsc_target,
			__NAMESPACE__ . '::' . (string) $arg['log_function'],
			'debug'
		);
		// Logger.
		}
		$cleaner->purgeUrl( $ahsc_target );
	}

	if ( \is_array( $ahsc_target ) ) {
		// Logger.
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			// Logger.
			AHSC_log(
				'hook::transition_post_status::' . (string) $arg['log_option'] . "::\n" . \implode( "::\n", $ahsc_target ),
				__NAMESPACE__ . '::' . (string) $arg['log_function'],
				'debug'
			);
			// Logger.
		}
		$cleaner->purgeUrls( $ahsc_target );
	}

	/*
	 * Deliberately no ahsc_is_purged here. This function performs a *targeted* purge of one
	 * page or a list of term archives; taking the global lock afterwards meant that purging a
	 * single page suppressed every purge site-wide for the next minute, including full ones
	 * for unrelated content. The lock is set only where a purgeAll actually happens.
	 */
}

/**
 * Returns the list of taxonomies to be passed as targets.
 *
 * @param int $post_id The taxonomies lists.
 * @return void
 */
 function ahsc_get_terms_target( $post_id ) {
	 global $ahsc_target;

	$post_type  = \get_post_type( $post_id );
	$taxonomies = \get_object_taxonomies( $post_type );
	$_ahsc_target     = array();

	foreach ( $taxonomies as $tax ) {
		$post_term_list = \get_the_terms( $post_id, $tax );

		if ( false === $post_term_list ) {
			continue;
		}

		foreach ( \get_the_terms( $post_id, $tax ) as $tt ) {
			$_ahsc_target[] = \get_term_link( $tt->term_id, $tax );
		}
	}
	$ahsc_target = $_ahsc_target;
}

/**
 * Whether a change to this post type can affect publicly cached output.
 *
 * Replaces the hardcoded allow-list this file used to carry, and is applied by *both*
 * purge paths. The list only ever gated post_updated; transition_post_status looked at no
 * post type at all, so an excluded type still purged the whole site whenever it published
 * or trashed something — which is exactly what queue and logging CPTs do, and exactly what
 * the list existed to prevent.
 *
 * The rule is core's own is_post_type_viewable(): can a visitor reach this type on the
 * front end at all? That covers Action Scheduler's scheduled-action, oembed_cache,
 * revision, shop_order, shop_webhook and shop_coupon without naming any of them, and it
 * lets a legitimate third-party public CPT — a portfolio, an events calendar — drive
 * purges automatically, which the fixed list silently refused to do.
 *
 * Some types are added on top because they are not viewable at a URL of their own but are
 * rendered *into* other pages — exactly the case "not viewable" gets wrong:
 *
 *   wp_template, wp_template_part  the theme's templates and parts, the header among them.
 *   wp_block                       synced patterns, embedded by reference in any number of
 *                                  pages. Unsynced patterns land here too; purging for those
 *                                  is harmless overreach, since their content was copied into
 *                                  each page when it was inserted.
 *   wp_global_styles               Site Editor > Styles: colours, typography, spacing — the
 *                                  CSS of every page on the site.
 *   customize_changeset            publishing in the Customizer. The changeset is internal,
 *                                  but publishing it applies site-wide settings. Its
 *                                  autosaves are drafts, which the status rule ignores, so
 *                                  only the publish purges.
 *
 * The first version of this list held only the two template types, and every other entry
 * here stopped purging as a result — 3.1.0 had no type rule on this path, so they all used
 * to. The font library (wp_font_family, wp_font_face) is left out on purpose: installing a
 * font changes nothing until global styles use it, and that save purges.
 *
 * Menus are refused outright, ahead of the filter: nav_menu_item and wp_navigation belong
 * to the menu handler in AHSC_Terms.php, which runs on the term toggle the settings screen
 * assigns them to. Purging them here as well would put them under the post toggle too.
 *
 * On WooCommerce specifically: an order is shown to the customer on the order-received page
 * and under My Account, but those are endpoints of the checkout and my-account pages, and
 * WC_Cache_Helper::prevent_caching() (wp_headers, priority 5) marks them no-cache/no-store
 * and defines DONOTCACHEPAGE. Our proxy honours that, so an order view is never cached and
 * a purge has nothing to invalidate. What an order does change publicly is stock and
 * sales-derived output, which this never caught anyway: WooCommerce writes stock through
 * its own data store, and under HPOS orders are not posts at all. Hook
 * woocommerce_product_set_stock if we want that, and purge the product permalink.
 *
 * @param string $post_type The post type slug.
 * @return bool
 */
function ahsc_post_type_may_purge( $post_type ) {
	if ( empty( $post_type ) || ! is_string( $post_type ) ) {
		return false;
	}

	if ( ahsc_is_menu_post_type( $post_type ) ) {
		return false;
	}

	$ahsc_not_viewable_but_rendered = array(
		'wp_template',
		'wp_template_part',
		'wp_block',
		'wp_global_styles',
		'customize_changeset',
	);

	$ahsc_may_purge = \in_array( $post_type, $ahsc_not_viewable_but_rendered, true ) ||
	                  \is_post_type_viewable( $post_type );

	/**
	 * Filters whether a change to a post type triggers a proxy cache purge.
	 *
	 * Lets a site opt a type in that is not publicly viewable but still affects output, or
	 * opt one out that is viewable but changes too often to be worth purging for.
	 *
	 * @param bool   $ahsc_may_purge Whether this type may drive a purge.
	 * @param string $post_type      The post type slug.
	 */
	return (bool) \apply_filters( 'ahsc_post_type_may_purge', $ahsc_may_purge, $post_type );
}

/**
 * Whether a post status transition changes what a visitor can see.
 *
 * Shared by both purge paths, for the same reason the post type rule is: they fire on
 * different events and used to filter on different axes, so each one's blind spot was
 * covered only by accident. post_updated does not fire for a scheduled post going live —
 * wp_publish_post() writes the row with $wpdb->update and calls wp_transition_post_status()
 * directly — so that reaches only ahsc_transition_post_status(). Conversely an unpublish
 * arrives there as 'draft', which its old new-status-only test dropped, leaving only
 * ahsc_post_updated() to catch it. Neither path should depend on the other noticing.
 *
 * The rule is 'publish' on either side of the transition, which is precisely what makes a
 * post appear in, or disappear from, what the proxy can serve:
 *
 *   draft   to publish   publishing
 *   future  to publish   a scheduled post going live
 *   publish to publish   editing something already public
 *   publish to draft     unpublishing
 *   publish to trash     deleting
 *   trash   to publish   restoring
 *
 * Two transitions that used to purge no longer do, on purpose: draft to future (scheduling
 * something that was never public) and draft to trash (discarding a draft). Neither changes
 * a single byte a visitor could have cached.
 *
 * @param string $new_status Status after the transition.
 * @param string $old_status Status before it. May be empty when the caller cannot supply it.
 * @return bool
 */
function ahsc_status_change_may_purge( $new_status, $old_status ) {
	return 'publish' === $new_status || 'publish' === $old_status;
}
