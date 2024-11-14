<?php
global $pagenow;
$allowed_cases = array( 'publish', 'future', 'trash' );
$target=array();
$is_json=(defined('REST_REQUEST') && REST_REQUEST) || true === wp_is_json_request() ||
         (isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), trailingslashit( rest_get_url_prefix() ) ) !== false)  ;
// phpcs:disable
if( ( ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || defined( 'DOING_AJAX' ) ) &&
( isset( $_REQUEST['wc-ajax'] )  && 'checkout' !== $_REQUEST['wc-ajax']) ||
'nav-menus.php' !== $pagenow){
	if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_del'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_mod'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_del']
	){
		\add_action( 'post_updated',  'ahsc_post_updated' , 20, 2 );

		if ( did_action( 'elementor/loaded' ) ) {
			add_action( 'elementor/editor/after_save', 'ahsc_post_updated', 10 , 2 );
		}
	}
}

if('nav-menus.php' !== $pagenow){
	if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_del'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_mod'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit'] ||
	   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_del']
	){

		//if ( ahsc_has_transient( 'ahsc_is_purged' ) ) {
			\add_action( 'transition_post_status', 'ahsc_transition_post_status' , 20, 3 );
			\add_action( 'pre_post_update',  'get_terms_target' , 20, 1 );
		//}

	}
}
// phpcs:enable
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
 function ahsc_post_updated($post_ID, $data) {
	/**
	 * I check whether the cleaning transient "ahsc_do_purge_deferred" is present in case I inhibit this action.
	 */
	$do_purge = ahsc_has_transient( 'ahsc_do_purge_deferred' );

	if ( $do_purge ) {
		return;
	}
	/**
	 * Disable the purge action if the transient 'ahsc_is_purged' is set.
	 */
	if ( ahsc_has_transient( 'ahsc_is_purged' ) ) {
		return;
	}
	 $cleaner =new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	/**
	 * If home page cleaning is set, there is no point in going any further, the entire site cache will be cleaned.
	 */

	if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')){
		// Logger.
		AHSC_log( 'hook::post_updated::home', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		// Logger.
	}
	$cleaner->purgeAll();
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
global $allowed_cases,$is_json,$target;
	/**
	 * Disable the purge action if the transient 'ahsc_is_purged' is set.
	 */
	if (  ahsc_has_transient( 'ahsc_is_purged' ) ) {
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

	/**
	 * Disable the purge action if the new status is not present in allorave casese array.
	 */
	if ( ! \in_array( $new_status, $allowed_cases, true ) ) {
		return;
	}

	/**
	 * For json call
	 */
	if ( $is_json ) {
		$do_purge             = ahsc_has_transient( 'ahsc_do_purge_deferred' );
		$do_purge_log_message = 'Transint presente';

		if ( ! $do_purge ) {
			$do_purge_log_message = 'Transint non presente presente lo imposto';
			ahsc_set_transient( 'ahsc_do_purge_deferred', \time(), MINUTE_IN_SECONDS );

			$cache_warmer =new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
			$cache_warmer->cache_warmer();
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
		'post'                 => $post,
	);

	/**
	 * If home page cleaning is set, there is no point in going any further, the entire site cache will be cleaned.
	 */
	if ( true === $options['is_publish_or_future'] &&  AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit'] ) {
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

		post_mod_cache_cleaner( $options );
		return;
	}

	$taxonomies = \get_object_taxonomies( $post->post_type );
	if ( empty( $taxonomies ) ) {
		return;
	}

	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_edit'] && true === $options['is_publish_or_future'] ) {
		$options['log_option'] = $options['log_option'] . '_archive_on_edit';
		$options['target']     = $target;

		post_mod_cache_cleaner( $options );
		return;
	}

	/**
	 * Delete items
	 */
	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_archive_on_del'] && true === $options['is_trashed'] ) {
		$options['log_option'] = $options['log_option'] . '_archive_on_del';
		$options['target']     = $target;

		post_mod_cache_cleaner( $options );
		return;
	}
}

/**
 * Proxy cache cleaner based on passed options.
 *
 * @param  array $arg .
 * @return void
 */
 function post_mod_cache_cleaner( $arg ) {
	 $cleaner       = new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	$target  = $arg['target'];

	if ( ! \is_array( $target ) ) {

		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')){
		// Logger.
		AHSC_log(
			'hook::transition_post_status::' . (string) $arg['log_option'] . '::' . $target,
			__NAMESPACE__ . '::' . (string) $arg['log_function'],
			'debug'
		);
		// Logger.
		}
		$cleaner->purgeUrl( $target );
	}

	if ( \is_array( $target ) ) {
		// Logger.
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			// Logger.
			AHSC_log(
				'hook::transition_post_status::' . (string) $arg['log_option'] . "::\n" . \implode( "::\n", $target ),
				__NAMESPACE__ . '::' . (string) $arg['log_function'],
				'debug'
			);
			// Logger.
		}
		$cleaner->purgeUrls( $target );
	}

	ahsc_set_transient( 'ahsc_is_purged', \time(), MINUTE_IN_SECONDS );
}

/**
 * Returns the list of taxonomies to be passed as targets.
 *
 * @param int $post_id The taxonomies lists.
 * @return void
 */
 function get_terms_target( $post_id ) {
	 global $target;

	$post_type  = \get_post_type( $post_id );
	$taxonomies = \get_object_taxonomies( $post_type );
	$_target     = array();

	foreach ( $taxonomies as $tax ) {
		$post_term_list = \get_the_terms( $post_id, $tax );

		if ( false === $post_term_list ) {
			continue;
		}

		foreach ( \get_the_terms( $post_id, $tax ) as $tt ) {
			$_target[] = \get_term_link( $tt->term_id, $tax );
		}
	}
	$target = $_target;
}