<?php

if ( ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() || defined('DOING_AJAX') && DOING_AJAX) ||
     (defined('REST_REQUEST') && REST_REQUEST) || true === wp_is_json_request() ||
     (isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), trailingslashit( rest_get_url_prefix() ) ) !== false)  ) {

	add_action( 'init', 'ahsc_deferred_purge' , 200, 0 );
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