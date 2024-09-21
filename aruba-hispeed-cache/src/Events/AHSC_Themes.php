<?php
\add_action( 'switch_theme', 'ahsc_purge_on_switch_theme' , 200, 3 );

/**
 * Fires after the theme is switched.
 *
 * @param  string   $new_name Name of the new theme.
 * @param  WP_Theme $new_theme Instance of the new theme.
 * @param  WP_Theme $old_theme Instance of the old theme.
 * @return void
 */
 function ahsc_purge_on_switch_theme( $new_name, $new_theme, $old_theme ) {
	$cleaner = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	$previous_name = $old_theme->__get( 'title' );
	$next_name     = $new_theme->__get( 'title' );

	/**
	 * If home page cleaning is set, there is no point in going any further, the entire site cache will be cleaned.
	 */

	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit']||
	     AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_del'] ) {
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			// Logger.
			AHSC_log( 'hook::switch_theme::home::' . $previous_name . '_to_' . $next_name, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}
		$cleaner->purgeAll();
	}
}