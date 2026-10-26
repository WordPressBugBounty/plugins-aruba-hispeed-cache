<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $pagenow,$ahsc_term_target;
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

/*
 * Menus purge from here, for every way a menu can change, on the *term* toggle.
 *
 * The settings screen lists menus under "A term or menu is edited, added or deleted",
 * below "Automatically clear whole cache when:", so this is ahsc_purge_archive_on_edit's
 * job and only its job. Menus used to be split three ways, none of them right:
 *
 *   - Appearance > Menus was hooked here, on classic themes only, gated on
 *     archive_on_edit || archive_on_del. && binds tighter than ||, and archive_on_del has
 *     no UI and defaults to true, so the term toggle could not turn menu purging off.
 *   - The Customizer and the REST API save menus as nav_menu_item posts, so they went
 *     through the post handlers and the *post* toggle. With that one off, nothing purged.
 *   - Block themes keep menus in wp_navigation posts, which are not publicly viewable, so
 *     the post type rule dropped them and editing navigation in the Site Editor purged
 *     nothing at all. That one was a regression: 3.1.0 had no type rule.
 *
 * Now one set of hooks, registered on every screen and for every theme type, covers all of
 * it, and ahsc_post_type_may_purge() refuses both menu post types so the post handlers
 * cannot purge them a second time under the wrong toggle.
 *
 * ! empty() rather than reading the key directly: a missing key would raise a warning and
 * read as off.
 */
if ( is_array( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'] ) &&
     ! empty( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit'] ) ) {

	// A classic menu saved or deleted: the Menus screen, the Customizer, the REST API.
	\add_action( 'wp_update_nav_menu', 'ahsc_queue_menu_purge', 200, 0 );
	\add_action( 'wp_delete_nav_menu', 'ahsc_queue_menu_purge', 200, 0 );

	// Menu items and block navigation are posts: published, edited, trashed, restored.
	\add_action( 'transition_post_status', 'ahsc_menu_post_transition', 200, 3 );

	// ...and deleted outright, which never goes through a status transition.
	\add_action( 'deleted_post', 'ahsc_menu_post_deleted', 200, 2 );

	// A menu assigned to or removed from a theme location: a theme mod, so no menu hook fires.
	\add_action( 'update_option_theme_mods_' . \get_option( 'stylesheet' ), 'ahsc_menu_locations_changed', 200, 2 );
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
	global $ahsc_term_target, $ahsc_term_is_menu;

	/*
	 * A menu is a term in the nav_menu taxonomy, and menus have their own handler now. Note
	 * it, so the delete handler stands aside instead of purging a second time.
	 */
	$ahsc_term_is_menu = ( 'nav_menu' === $taxonomy );
	$ahsc_term_target  = \get_term_link( $term, $taxonomy );
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
	global $ahsc_term_target, $ahsc_term_is_menu;

	// Menus are purged by ahsc_queue_menu_purge(), on wp_delete_nav_menu.
	if ( ! empty( $ahsc_term_is_menu ) ) {
		return;
	}

	$cleaner = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	//$option  = $this->container->get_service( 'ahsc_get_option' );

	/*
	 * Mode switch on the *term* toggle: "A term or menu is edited, added or deleted",
	 * under "Automatically clear whole cache when:". It used to read
	 * ahsc_purge_homepage_on_del, which has no UI at all and defaults to true, so this
	 * branch was effectively unconditional and no setting on the screen could reach the
	 * targeted purge below it.
	 */
	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit']) {
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			// Logger.
			AHSC_log( 'hook::edited_term::home', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}
		$cleaner->purgeAll();
		return;
	}

	$target = $ahsc_term_target;
	if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
		// Logger.
		AHSC_log( 'hook::edited_term::' . $target, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		// Logger.
	}
	$cleaner->purgeUrl( $target );
}

/**
 * The two post types that hold menus: classic menu items and block-theme navigation.
 *
 * @param string $post_type Post type slug.
 * @return bool
 */
function ahsc_is_menu_post_type( $post_type ) {
	return in_array( $post_type, array( 'nav_menu_item', 'wp_navigation' ), true );
}

/**
 * Queue a menu purge when a menu post changes what a visitor can see.
 *
 * Same status rule as the post handlers: 'publish' on either side of the transition. On the
 * Menus screen, adding an item creates it as a draft long before the menu is saved, and
 * those draft-to-draft writes must not purge anything.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       The post.
 * @return void
 */
function ahsc_menu_post_transition( $new_status, $old_status, $post ) {
	if ( ! ( $post instanceof WP_Post ) || ! ahsc_is_menu_post_type( $post->post_type ) ) {
		return;
	}

	if ( ! ahsc_status_change_may_purge( $new_status, $old_status ) ) {
		return;
	}

	ahsc_queue_menu_purge();
}

/**
 * Queue a menu purge when a published menu post is deleted.
 *
 * wp_delete_post() only sends posts and pages to the trash; everything else, menu items
 * included, is deleted outright, and that never goes through a status transition. It is how
 * the Menus screen, the Customizer and the REST API all remove items.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    The post as it was before deletion.
 * @return void
 */
function ahsc_menu_post_deleted( $post_id, $post = null ) {
	if ( ! ( $post instanceof WP_Post ) || ! ahsc_is_menu_post_type( $post->post_type ) ) {
		return;
	}

	if ( 'publish' !== $post->post_status ) {
		return;
	}

	ahsc_queue_menu_purge();
}

/**
 * Queue a menu purge when the menu assigned to a theme location changes.
 *
 * Locations live in the nav_menu_locations theme mod, so assigning a menu to the header on
 * the Manage Locations tab fires no menu hook at all. Comparing that one key keeps every
 * other theme mod change — colours, header image — out of this.
 *
 * @param mixed $old_value Previous theme mods.
 * @param mixed $value     New theme mods.
 * @return void
 */
function ahsc_menu_locations_changed( $old_value, $value ) {
	$old_locations = ( is_array( $old_value ) && isset( $old_value['nav_menu_locations'] ) ) ? $old_value['nav_menu_locations'] : array();
	$new_locations = ( is_array( $value ) && isset( $value['nav_menu_locations'] ) ) ? $value['nav_menu_locations'] : array();

	// Loose on purpose: the same assignments in a different order are not a change.
	if ( $old_locations != $new_locations ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
		ahsc_queue_menu_purge();
	}
}

/**
 * Arrange for the whole cache to be purged once, at the end of this request.
 *
 * At the end rather than straight away, because a menu save is many writes. On the Menus
 * screen wp_update_nav_menu fires twice: from wp_update_nav_menu_object(), *before*
 * wp_nav_menu_update_menu_items() has written a single item, and again once it has. The old
 * handler purged on the first and let a guard swallow the second, so the proxy was emptied
 * and only then were the items saved — a request landing in between cached the old menu
 * again, which reads as "the purge did not happen". On shutdown every write has landed, and
 * however many of these hooks fired, the purge runs once.
 *
 * Whole cache, because that is what the setting promises and because a menu appears on
 * practically every page.
 *
 * @return void
 */
function ahsc_queue_menu_purge() {
	global $ahsc_menu_purge_queued;

	if ( ! empty( $ahsc_menu_purge_queued ) ) {
		return;
	}

	$ahsc_menu_purge_queued = true;

	\add_action( 'shutdown', 'ahsc_purge_for_menus', 90 );
}

/**
 * Purge the whole proxy cache after a menu changed.
 *
 * @return void
 */
function ahsc_purge_for_menus() {
	$cleaner = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	$cleaner->setPurger( AHSC_PURGER );

	if ( class_exists( 'ArubaSPA\HiSpeedCache\Debug\Logger' ) ) {
		// Logger.
		AHSC_log( 'hook::menu::home', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		// Logger.
	}

	$cleaner->purgeAll();
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
 * @return void
 */
function ahsc_purge_archive_on_edit( $term_id, $tt_id, $taxonomy ) {

	/*
	 * Renaming a menu in the Customizer or over REST fires edited_term for the nav_menu
	 * taxonomy as well as wp_update_nav_menu. The menu handler owns that, once, on shutdown.
	 */
	if ( 'nav_menu' === $taxonomy ) {
		return;
	}

	$cleaner = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	$cleaner->setPurger( AHSC_PURGER );
	//$option  = $this->container->get_service( 'ahsc_get_option' );
	/* Same mode switch as the delete handler, on the term toggle rather than the post one. */
	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit']) {
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