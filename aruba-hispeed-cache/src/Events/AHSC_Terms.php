<?php
global $pagenow,$term_target;
$nav_purged=false;
if( 'nav-menus.php' !== $pagenow){
	//if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_del']){
	if(is_array(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit']){
		/**
		 * Fires when deleting a term, before any modifications are made to posts or terms.
		 */
		\add_action( 'pre_delete_term', 'ahsc_set_term_uri' , 200, 2 );

		/**
		 * Fires after a term has been delete, and the term cache has been cleaned.
		 */
		\add_action( 'delete_term',  'ahsc_purge_archive_on_delete' , 200, 0 );
	}
}

if ( ! ahsc_current_theme_is_fse_theme() && 'nav-menus.php' === $pagenow ) {
	if(is_array(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit']||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_del' ]){

		\add_action( 'wp_update_nav_menu', 'ahsc_update_nav_menu' , 200, 0 );
	}
}

if( 'nav-menus.php' !== $pagenow) {
	if ( is_array(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit'] ) {
		\add_action( 'edited_term', 'ahsc_purge_archive_on_edit' , 200, 3 );
	}
}

/**
 * Fires when deleting a term, before any modifications are made to posts or terms.
 *
 * @param int    $term Term ID.
 * @param string $taxonomy Taxonomy name.
 * @return void
 */
 function ahsc_set_term_uri( $term, $taxonomy ) {
	global $term_target;
	$term_target = \get_term_link( $term, $taxonomy );
}

/**
 * Fires after a term has been updated, and the term cache has been cleaned.
 *
 * @see https://developer.wordpress.org/reference/hooks/delete_term/.
 *
 * param int     $term Term ID.
 * param int     $tt_id Term taxonomy ID.
 * param string  $taxonomy Taxonomy slug.
 * param WP_Term $deleted_term  Copy of the already-deleted term.
 * param array   $object_ids List of term object IDs.
 *
 * @return void
 */
 function ahsc_purge_archive_on_delete() {
	global $term_target;
	$cleaner = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	//$option  = $this->container->get_service( 'ahsc_get_option' );

	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_del']) {
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			// Logger.
			AHSC_log( 'hook::edited_term::home', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}
		$cleaner->purgeAll();
		return;
	}

	$target = $term_target;
	if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
		// Logger.
		AHSC_log( 'hook::edited_term::' . $target, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		// Logger.
	}
	$cleaner->purgeUrl( $target );
}

/**
 * Fires after a nav menu has been updated, and the term cache has been cleaned.
 *
 * @see https://developer.wordpress.org/reference/hooks/wp_update_nav_menu/
 *
 * param int   $menu_id   ID of the updated menu.
 * param array $menu_data An array of menu data.
 *
 * @return void
 */
function ahsc_update_nav_menu() {
global $nav_purged;
	if ( ! $nav_purged ) {
		$cleaner = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
		$cleaner->setPurger( AHSC_PURGER );
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			// Logger.
			AHSC_log( 'hook::wp_update_nav_menu::home', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}
		$cleaner->purgeAll();
	}
	$nav_purged = true;
}

/**
 * Fires after a term has been updated, and the term cache has been cleaned.
 *
 * @see https://developer.wordpress.org/reference/hooks/edited_term/.
 *
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 *
 * array  $args     Arguments passed to wp_update_term() added in 6.1 wp core remove for compatibility wiht 5.6.
 *
 * @return void
 */
function ahsc_purge_archive_on_edit( $term_id, $tt_id, $taxonomy ) {

	$cleaner = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	$cleaner->setPurger( AHSC_PURGER );
	//$option  = $this->container->get_service( 'ahsc_get_option' );
	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit']) {
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			// Logger.
			AHSC_log( 'hook::edited_term::home', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}
		$cleaner->purgeAll();
		return;
	}

	$target = \get_term_link( $term_id, $taxonomy );
	if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
		// Logger.
		AHSC_log( 'hook::edited_term::' . $target, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		// Logger.
	}
	$cleaner->purgeUrl( $target );
}