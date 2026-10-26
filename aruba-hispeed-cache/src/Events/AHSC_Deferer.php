<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
 * Consume the deferred purge on shutdown, not on init of some later request.
 *
 * The flag is set while a post is saved and says "a purgeAll is owed". It used to be read
 * at init priority 200, and only on AJAX/REST requests — which meant two things. The
 * consumer had already run by the time the save set the flag, so the work always waited for
 * the *next* such request; and the flag expires after 60 seconds, so if none arrived in that
 * window the purge was dropped silently. Publish from the block editor, close the tab, and
 * the post stayed stale in the proxy until its TTL.
 *
 * On shutdown the same request that queued the purge performs it, at the very end of that
 * request, so nothing depends on what traffic happens to follow. (The end, not after the
 * response: WordPress does not call fastcgi_finish_request(), so the client is still
 * waiting while shutdown hooks run — one loopback call, but not free.) Repeated events inside
 * one request still collapse into a single purgeAll, because they all set the same flag.
 *
 * Registration is limited to admin-side requests. A front-end request never queues a purge,
 * and the check itself is not free: ahsc_has_transient() reads two non-autoloaded options,
 * which on a site without the APCu drop-in is a query.
 */
$ahsc_defer_here = is_admin() ||
	( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( defined('DOING_AJAX') && DOING_AJAX ) ||
	(defined('REST_REQUEST') && REST_REQUEST) || true === wp_is_json_request() ||
	(isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), trailingslashit( rest_get_url_prefix() ) ) !== false);

if ( $ahsc_defer_here ) {
	add_action( 'shutdown', 'ahsc_deferred_purge', 100 );
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
 function ahsc_deferred_purge() {
	 $cleaner =new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	/**
	 * I check whether the cleaning transient is present in case. I remove it and clear the entire proxy cache.
	 */
	$do_purge = ahsc_has_transient( 'ahsc_do_purge_deferred' );
	if ( $do_purge ) {
		/**
		 * I clear the entire proxy cache.
		 */
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')){
			// Logger.
			AHSC_log( __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}
		$cleaner->purgeAll();
		ahsc_delete_transient( 'ahsc_do_purge_deferred' );
	}
}