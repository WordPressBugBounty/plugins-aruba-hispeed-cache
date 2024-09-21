<?php
\add_action( 'activated_plugin',  'ahsc_purge_on_plugin_actions' , 200, 1 );
\add_action( 'deactivate_plugin', 'ahsc_purge_on_plugin_actions' , 200, 1 );
\add_action( 'delete_plugin',  'ahsc_purge_on_plugin_actions' , 200, 1 );

/**
 * Ahsc_purge_on_plugin_actions
 * Purge cache on plugin activation, deativation
 *
 * @param string $plugin The plugin name/file.
 *
 * @since 1.2.0
 *
 * @return void
 */
 function ahsc_purge_on_plugin_actions( $plugin ) {
	 $cleaner =new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	/**
	 * If home page cleaning is set, there is no point in going any further, the entire site cache will be cleaned.
	 */

	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit'] ||
	     AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_del'] ) {
		global $logger;
		if(class_exists('\ArubaSPA\HiSpeedCache\Debug\Logger')){
		  // Logger.
		  AHSC_log( 'hook::' . \current_filter() . '::home::' . $plugin, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		  // Logger.
		}
		$cleaner->purgeAll();
		return;
	}
}