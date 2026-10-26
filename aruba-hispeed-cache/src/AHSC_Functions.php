<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use ArubaSPA\HiSpeedCache\Debug\Logger;

/**
 * operation at plugin activation
 * @return void
 */
function AHSC_activation( ) {
	// controllo la presenza del wp-cli.
	$is_cli = (defined('WP_CLI') && WP_CLI);
   // Controllo se è una richiesta HTTP reale (admin)
	$is_admin_ui = (
		isset($_SERVER['REQUEST_METHOD']) &&
		is_admin() &&
		isset($_SERVER['REQUEST_URI']) &&
		strpos(sanitize_url( wp_unslash($_SERVER['REQUEST_URI'])), 'plugins.php') !== false
	);

	if ($is_cli) {
		// attivazione
		AHSC_activation_operation();
	} else{
		if ($is_admin_ui) {
			$check = AHSC_check();
			if ( ! $check['is_aruba_server'] ) {
				AHSC_deactivate_me();
				wp_die( esc_attr( ahsc_get_check_notice( $check ) ), 'Aruba HiSpeed Cache dependency check', array( 'back_link' => true ) );
			} else {
				AHSC_activation_operation();
			}
		}
	}
}

function AHSC_activation_operation(){
	$options = AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'];
	if ( ! $options ) {
		$options = array_map(
			function( $opt ) {
				return $opt['default'];
			},
			AHSC_OPTIONS_LIST_DEFAULT
		);
	}

	// The cache warmer was removed in 3.1.0: drop its leftovers on installs
	// upgrading from an earlier version.
	unset( $options['ahsc_cache_warmer'] );
	ahsc_remove_obsolete_options();

	\update_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'] , $options );
	\update_site_option( AHSC_VERSION_OPTION, AHSC_VERSION );

	AHSC_remove_htaccess();
	if(array_key_exists('ahsc_static_cache',AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'])){
		if(isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_static_cache'])){
			if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_static_cache']){
				AHSC_edit_htaccess();
			}
		}
	}
}
/**
 * operation at plugin deactivation
 * @return void
 */
function AHSC_deactivation(  ) {

	\delete_site_option( 'aruba_hispeed_cache_options' );
	\delete_site_option( AHSC_VERSION_OPTION );
	ahsc_remove_obsolete_options();
	AHSC_remove_htaccess();
	/*
	 * Deletes the drop-in only when it is ours. This used to be unconditional, which
	 * meant deactivating this plugin destroyed Redis Object Cache's or W3TC's drop-in if
	 * one was installed.
	 */
	ahsc_dropin_remove();
}
/**
 * Delete options that older versions of the plugin left behind.
 *
 * Was AHSC_remove_cache_warmer_leftovers(), which named the one row it happened to
 * delete rather than the job it does. Every future removal belongs in here, next to a
 * note saying which version dropped the feature, so there is one place to look and one
 * function for ahsc_maybe_run_upgrade() to call.
 *
 * Keep every deletion idempotent: this runs on activation, on deactivation and on
 * upgrade, and may run more than once if two requests notice the same upgrade together.
 *
 * @return void
 */
function ahsc_remove_obsolete_options() {
	// Cache warmer, dropped in 3.1.0.
	\delete_option( 'ahsc_do_cache_warmer' );
	if ( \is_multisite() ) {
		\delete_site_option( 'ahsc_do_cache_warmer' );
	}
}

/**
 * Site option holding the plugin version the upgrade routine last ran for.
 *
 * Written at activation since long before this routine existed, and until now never
 * read by anything.
 */
const AHSC_VERSION_OPTION = 'aruba_hispeed_cache_version';

/*
 * Run one-off chores after a plugin update.
 *
 * A plugin update fires no activation hook, so anything that only runs from
 * register_activation_hook() never happens on an upgraded site — which is why the cache
 * warmer's leftover row survived every update since 3.1.0. This closes that gap and
 * gives later chores somewhere to live.
 *
 * The fast path is one autoloaded option read and a string comparison, the same shape
 * as ahsc_dropin_maybe_sync(). Deliberately separate from that one: the drop-in carries
 * its own version and has to be re-copied when *it* changes, whether or not the plugin
 * version moved. Two version numbers, two jobs.
 */
\add_action( 'plugins_loaded', 'ahsc_maybe_run_upgrade', 5 );

if ( ! \function_exists( 'ahsc_maybe_run_upgrade' ) ) {

	/**
	 * Compare the recorded version against the running one and run upgrade chores.
	 *
	 * @return void
	 */
	function ahsc_maybe_run_upgrade() {
		$ahsc_recorded = \get_site_option( AHSC_VERSION_OPTION, '' );

		if ( AHSC_VERSION === $ahsc_recorded ) {
			return;
		}

		/*
		 * Record first, then work. If a chore ever fatals, the version is already stored
		 * and the next request carries on instead of hitting the same fatal forever — a
		 * broken chore should not take the site down with it. The cost is that such a
		 * chore is skipped rather than retried, which is the right trade for deletions.
		 */
		\update_site_option( AHSC_VERSION_OPTION, AHSC_VERSION );

		ahsc_remove_obsolete_options();

		if ( \class_exists( '\ArubaSPA\HiSpeedCache\Debug\Logger' ) ) {
			// Logger.
			AHSC_log(
				sprintf( 'Upgrade chores ran: %s to %s.', '' === $ahsc_recorded ? 'unknown' : $ahsc_recorded, AHSC_VERSION ),
				'upgrade',
				'info'
			);
			// Logger.
		}
	}
}
/**
 * operation at plugin check requirement
 * @return void
 */
function AHSC_deactivate_me() {
	if ( \function_exists( 'deactivate_plugins' ) ) {
		\deactivate_plugins( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASENAME']);
	}
}
/**
 * create Notice in plugin admin page
 *
 * @param $handle string  notice html id
 * @param $html_class string notice general class es error,warning etc.etc.
 * @param $content string notice content
 *
 */
function AHSC_Notice_Render(  $handle,  $html_class,  $content, $return=false) {
	$HTML_CLASS = array(
		'error'   => 'notice notice-error',
		'warning' => 'notice notice-warning',
		'success' => 'notice notice-success',
		'info '   => 'notice notice-info',
	);

	if(!$return){
	printf(
		'<div id="%1$s" class="%2$s"><p>%3$s</p></div>',
		esc_attr( $handle ),
		esc_attr( $HTML_CLASS[$html_class] ),
		wp_kses_post( $content )
	);
	}else{
		return sprintf(
			'<div id="%1$s" class="%2$s"><p>%3$s</p></div>',
			esc_attr( $handle ),
			esc_attr( $HTML_CLASS[$html_class] ),
			wp_kses_post( $content )
		);
	}
}

/**
 * Outputs an admin notice.
 *
 * The core equivalent, wp_admin_notice(), only exists from WordPress 6.4.0 while the
 * plugin declares "Requires at least: 6.2", so the markup is built here instead. It
 * reproduces byte for byte what wp_kses_post( wp_get_admin_notice() ) returns, which is
 * covered by a regression test comparing the two outputs.
 *
 * The delegation to core that used to sit at the top of this function was removed on
 * purpose. It made the notice fatal-free on 6.2/6.3, but Plugin Check's
 * WP_Functions_Compatibility_Check is a token scan: it flagged the literal
 * wp_admin_notice() call regardless of the function_exists() guard around it, and being a
 * native check rather than a PHPCS sniff it cannot be silenced with an annotation.
 * Building the markup unconditionally is the only way to keep 6.2 support and a clean
 * report without resorting to a dynamic call that hides the reference from the scanner.
 *
 * Trade-off: on WordPress 6.4 and later this notice no longer passes through the
 * wp_admin_notice action nor the wp_admin_notice_args / wp_admin_notice_markup filters.
 *
 * @param string $message The message for the admin notice. May contain HTML.
 * @param array  $args    Supported keys: 'type', 'dismissible', 'id',
 *                        'additional_classes', 'paragraph_wrap'.
 *
 * @return void
 */
function ahsc_admin_notice( $message, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'type'               => '',
			'dismissible'        => false,
			'id'                 => '',
			'additional_classes' => array(),
			'paragraph_wrap'     => true,
		)
	);

	$classes = 'notice';

	if ( is_string( $args['type'] ) && '' !== trim( $args['type'] ) ) {
		$classes .= ' notice-' . trim( $args['type'] );
	}

	if ( true === $args['dismissible'] ) {
		$classes .= ' is-dismissible';
	}

	if ( is_array( $args['additional_classes'] ) && ! empty( $args['additional_classes'] ) ) {
		$classes .= ' ' . implode( ' ', $args['additional_classes'] );
	}

	if ( false !== $args['paragraph_wrap'] ) {
		$message = '<p>' . $message . '</p>';
	}

	$id = is_string( $args['id'] ) ? trim( $args['id'] ) : '';

	if ( '' !== $id ) {
		printf(
			'<div id="%1$s" class="%2$s">%3$s</div>',
			esc_attr( $id ),
			esc_attr( $classes ),
			wp_kses_post( $message )
		);
	} else {
		printf(
			'<div class="%1$s">%2$s</div>',
			esc_attr( $classes ),
			wp_kses_post( $message )
		);
	}
}

/**
 * create link in plugin page
 * @param array $actions The plugin action links.
 * @return array
 */
function AHSC_plugin_action_links($actions){

	$settings_link = \sprintf(
		'<a href="%s">%s</a>',
		(( ! is_multisite() ) ? admin_url( 'options-general.php?page=aruba-hispeed-cache') : network_admin_url('settings.php?page=aruba-hispeed-cache')) ,
		\esc_html(__( 'Settings', 'aruba-hispeed-cache' ))
	);

	$support_link = \sprintf(
		'<a href="%s" target="_blank">%s</a>',
		AHSC_LOCALIZE_LINK['link_assistance'][strtolower(substr( get_bloginfo ( 'language' ), 0, 2 ))],
		\esc_html(__( 'Customer support', 'aruba-hispeed-cache' ))
	);

	\array_unshift( $actions, $settings_link, $support_link );

	return $actions;

}

if ( ! \function_exists( 'ahsc_get_site_home_url' ) ) {
	/**
	 * Return the complete home url of site.
	 *
	 * @return string
	 */
	function ahsc_get_site_home_url() {
		$home_uri = \trailingslashit( \home_url() );

		if ( \function_exists( 'icl_get_home_url' ) ) {
			$home_uri = \trailingslashit( \icl_get_home_url() );
		}

		return $home_uri;
	}
}
/*

if ( ! \function_exists( 'ahsc_save_options' ) ) {
	**
	 * Save plugin options
	 *
	 * @return void
	 *
	function ahsc_save_options(  ) {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			if ( isset( $_POST['ahsc_settings_save'] ) && isset( $_POST['ahs-settings-nonce'] ) && \wp_verify_nonce( \sanitize_key( \wp_unslash( $_POST['ahs-settings-nonce'] ) ), 'ahs-save-settings-nonce' ) ) {
				$new_options = array();
				//var_dump(ABSPATH . 'wp-config.php');
				$wpc_transformer = new HASC_WPCT(  ABSPATH . 'wp-config.php' );
				foreach ( array_keys( AHSC_OPTIONS_LIST ) as $opt_key ) {
					if($opt_key==="ahsc_cron_status"  ){

							if(isset($_POST[ $opt_key ])){
								//var_dump("non disabilito cron ");
								$wpc_transformer->update( 'constant', 'DISABLE_WP_CRON', 'false', array( 'raw' => true, 'normalize' => true ));
								$new_options[ $opt_key ]= 'false';
							}else{
								//var_dump("disabilito cron ");
								$wpc_transformer->update( 'constant', 'DISABLE_WP_CRON', 'true', array( 'raw' => true, 'normalize' => true ));
								$new_options[ $opt_key ]= 'true';
							}

					}elseif($opt_key==="ahsc_cron_time"){

						if(isset($_POST[ $opt_key ])){
							//var_dump("setto time ");
							$wpc_transformer->update( 'constant', 'WP_CRON_LOCK_TIMEOUT', "'".absint($_POST[ $opt_key ])."'", array( 'raw' => true, 'normalize' => true ));
							$new_options[ $opt_key ]= sanitize_text_field( wp_unslash($_POST[ $opt_key ]));
						}else{
							//var_dump("non setto time ");
							$wpc_transformer->remove('constant', 'WP_CRON_LOCK_TIMEOUT');
						}
					}elseif($opt_key!=="ahsc_dns_preconnect_domains"){
						$new_options[ $opt_key ] = ( isset( $_POST[ $opt_key ] ) ) ? true : false; 
					}else{
						if(isset( $_POST[ $opt_key ] ) ){
							$trans_domain_list = explode( "\n", trim( sanitize_text_field( wp_unslash($_POST[ $opt_key ])) ) );
							foreach ( $trans_domain_list as $index => $string ) {
								if ( strpos( $string, $_SERVER['SERVER_NAME'] ) !== false ) {
									unset( $trans_domain_list[ $index ] );
								}
							}
							$new_options[ $opt_key ] =$trans_domain_list;
						}else{
							$new_options[ $opt_key ] ="";
						}
					}
				}

				if ( \update_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'], $new_options ) ) {
					$content = \esc_html( __('Settings saved.', 'aruba-hispeed-cache') );
					AHSC_Notice_Render('ahs_settings_saved', 'success',$content);
				}
			}
		}
	}
}

*/
if ( ! \function_exists( 'ahsc_reset_options' ) ) {
	/**
	 * Reset plugin options
	 *
	 * @return string The confirmation message.
	 */
	function ahsc_reset_options(  ) {
        $new_options = array();

		foreach ( array_keys( AHSC_OPTIONS_LIST_DEFAULT ) as $opt_key ) {
			$new_options[ $opt_key ] = AHSC_OPTIONS_LIST_DEFAULT[$opt_key]['default'];
		}
		    $wpc_transformer = new HASC_WPCT(  ABSPATH . 'wp-config.php' );

		    $wpc_transformer->remove('constant', 'DISABLE_WP_CRON');
		    $wpc_transformer->remove('constant', 'WP_CRON_LOCK_TIMEOUT');

			if ( isset( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc'] ) &&
			     AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc'] ){
					// Ownership-checked; see ahsc_dropin_remove().
					ahsc_dropin_remove();
			}

		    \update_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'], $new_options );

			$content = \esc_html( __('Original settings restored.', 'aruba-hispeed-cache') );
			return $content;

	}
}


if ( ! \function_exists( 'ahsc_has_transient' ) ) {

	/**
	 * Read one of the plugin's own transients.
	 *
	 * These three helpers exist for one reason now: picking site transients on multisite
	 * and ordinary ones elsewhere. Every read, write and delete of a plugin transient goes
	 * through them, so the storage decision is made in one place.
	 *
	 * They used to force $GLOBALS['_wp_using_ext_object_cache'] to false around each call,
	 * pushing the value into wp_options rather than the object cache. That was a
	 * workaround for the drop-in's broken gate: with APCu loaded but disabled, core
	 * believed in an external object cache that silently dropped everything, so a
	 * transient written through the normal API did not survive the request. Item 13 fixed
	 * the gate — an object cache core believes in is now one that works — so the flipping
	 * is gone.
	 *
	 * It had to go rather than merely could: values written through the flip and deleted
	 * through a plain delete_transient() left rows in wp_options that nothing would ever
	 * reap — the daily sweep below is what now clears them.
	 *
	 * @param  string $transient Transient name.
	 * @return mixed The stored value, or false when absent.
	 */
	function ahsc_has_transient( $transient ) {
		$transient_value = ( \is_multisite() )
			? \get_site_transient( (string) $transient )
			: \get_transient( (string) $transient );

		return ( false !== $transient_value ) ? $transient_value : false;
	}
}

if ( ! \function_exists( 'ahsc_set_transient' ) ) {

	/**
	 * Write one of the plugin's own transients. See ahsc_has_transient().
	 *
	 * @param  string  $transient  Transient name.
	 * @param  mixed   $value      Value to store.
	 * @param  integer $expiration Lifetime in seconds; 0 for no expiry.
	 * @return bool
	 */
	function ahsc_set_transient( $transient, $value, $expiration = 0 ) {
		return ( \is_multisite() )
			? \set_site_transient( $transient, $value, $expiration )
			: \set_transient( $transient, $value, $expiration );
	}
}

if ( ! \function_exists( 'ahsc_delete_transient' ) ) {

	/**
	 * Delete one of the plugin's own transients. See ahsc_has_transient().
	 *
	 * @param  string $transient Transient name.
	 * @return bool
	 */
	function ahsc_delete_transient( $transient ) {
		if ( class_exists( 'ArubaSPA\HiSpeedCache\Debug\Logger' ) ) {
			// Logger.
			AHSC_log( 'hook::transient::delete::' . $transient, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}

		return ( \is_multisite() )
			? \delete_site_transient( $transient )
			: \delete_transient( $transient );
	}
}

/*
 * Core schedules a daily `delete_expired_transients` event (wp-admin/admin.php) and hooks
 * the function of the same name to it with no arguments. That callback opens with
 * `if ( ! $force_db && wp_using_ext_object_cache() ) { return; }`, so on an install with
 * the APCu drop-in it returns immediately and does nothing — and get_transient()'s lazy
 * delete-on-read is skipped for the same reason. Nothing in WordPress would then ever
 * remove an expired `_transient_*` row from wp_options again.
 *
 * Rather than register an event of our own, hang the forced cleanup on the one core has
 * already scheduled: nothing to schedule on activation, nothing to unschedule on
 * deactivation or uninstall, and it inherits core's cadence. Priority 20 so it runs after
 * core's own callback has no-opped.
 *
 * This replaces the delete_expired_transients( true ) calls that used to run inside
 * WpPurger::purgeUrl(), purgeUrls() and purgeAll() — two unindexed self-join DELETEs over
 * wp_options on every post save, comment and term edit, to reclaim rows that accumulate
 * over days. The manual "clear expired transients" admin action is unaffected.
 */
\add_action( 'delete_expired_transients', 'ahsc_delete_expired_transients_forced', 20 );

if ( ! \function_exists( 'ahsc_delete_expired_transients_forced' ) ) {

	/**
	 * Delete expired transients from the database on core's daily cleanup event.
	 *
	 * Only does anything when an external object cache is in use. Without one, core's own
	 * callback on this same event has already deleted these exact rows, and repeating the
	 * query would be a second unindexed scan of wp_options for nothing.
	 *
	 * @return void
	 */
	function ahsc_delete_expired_transients_forced() {
		if ( ! \wp_using_ext_object_cache() ) {
			return;
		}

		\delete_expired_transients( true );

		if ( \class_exists( '\ArubaSPA\HiSpeedCache\Debug\Logger' ) ) {
			// Logger.
			AHSC_log( 'ALL', 'Clear Expired Transient (scheduled)' );
			// Logger.
		}
	}
}

/**
 * Option holding the last time the fallback transient sweep ran, as a Unix timestamp.
 *
 * Blog-scoped on purpose rather than a site option: delete_expired_transients() works on
 * $wpdb->options, which on multisite is the current blog's table, so every blog has to
 * sweep its own. Autoloaded, so the check below is a cache hit rather than a query.
 */
const AHSC_TRANSIENT_SWEEP_OPTION = 'ahsc_transient_sweep';

/** How often the fallback sweep may run. */
const AHSC_TRANSIENT_SWEEP_INTERVAL = DAY_IN_SECONDS;

/*
 * Fallback for sites that have switched WP-Cron off.
 *
 * The plugin ships that switch itself: the "Enable Wp-Cron" toggle writes
 * define( DISABLE_WP_CRON, true ) into wp-config.php. With it set the daily
 * delete_expired_transients event never fires, and nothing else on this host runs
 * wp-cron.php either — so the hook above is dead, and with the APCu drop-in installed
 * get_transient() has already stopped cleaning up on read. Expired _transient_* rows would
 * accumulate in wp_options forever, which is worse than what the plugin did before the
 * cleanup moved off the purge path.
 *
 * So when cron is off, sweep on shutdown instead, at most once a day. On requests that do
 * not sweep the guard costs one autoloaded option read.
 *
 * It tests the constant rather than the ahsc_enable_cron option deliberately: the host, or
 * the site owner with an editor, can set DISABLE_WP_CRON in wp-config.php without this
 * plugin ever knowing. What matters is whether cron can run, not who turned it off.
 *
 * Not covered: a site where cron is nominally enabled but silently never fires. There is
 * no cheap way to detect that from here.
 */
\add_action( 'shutdown', 'ahsc_maybe_sweep_expired_transients', 100 );

if ( ! \function_exists( 'ahsc_maybe_sweep_expired_transients' ) ) {

	/**
	 * Delete expired transients from the database when WP-Cron cannot do it.
	 *
	 * @return void
	 */
	function ahsc_maybe_sweep_expired_transients() {
		if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
			return;
		}

		$ahsc_now  = time();
		$ahsc_last = (int) \get_option( AHSC_TRANSIENT_SWEEP_OPTION, 0 );

		if ( $ahsc_last > 0 && ( $ahsc_now - $ahsc_last ) < AHSC_TRANSIENT_SWEEP_INTERVAL ) {
			return;
		}

		/*
		 * Claim the slot before doing the work, so two requests arriving together do not both
		 * run the sweep. Not airtight — there is a window between the read and the write — but
		 * the worst case is one redundant DELETE in a day.
		 */
		\update_option( AHSC_TRANSIENT_SWEEP_OPTION, $ahsc_now, true );

		\delete_expired_transients( true );

		if ( \class_exists( '\ArubaSPA\HiSpeedCache\Debug\Logger' ) ) {
			// Logger.
			AHSC_log( 'ALL', 'Clear Expired Transient (cron disabled)' );
			// Logger.
		}
	}
}

if ( ! \function_exists( 'ahsc_has_notice' ) ) {

	/**
	 * Return bool if transient notice check is set.
	 *
	 * @param  bool $remove Set to true to clean the transinte if present.
	 * @return mixed
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	function ahsc_has_notice( $remove = null ) {
		$has_notice = ahsc_has_transient( AHSC_CHECKER['transient_name'] );

		if ( false !== $has_notice && true === $remove ) {
			/*
			 * Through the helper, like the read above. These two disagreeing is what made the
			 * service-status notice unreachable: the value was read from one store and deleted
			 * from the other, so the delete never removed what the read had just found.
			 */
			ahsc_delete_transient( AHSC_CHECKER['transient_name'] );

			return false;
		}

		return $has_notice;
	}
}

if ( ! \function_exists( 'ahsc_get_debug_file_content' ) ) {
	/**
	 * Return the contente of log file.
	 *
	 * @return string
	 */
	function ahsc_get_debug_file_content() {
		global $wp_filesystem,$logger;
		\WP_Filesystem();
		if (class_exists('ArubaSPA\HiSpeedCache\Debug\Logger') ) {
			if ( \file_exists( Logger::get_log_file_path_name() ) ) {
				return $wp_filesystem->get_contents( Logger::get_log_file_path_name() );
			}
		}
		return false;
	}
}

if ( ! \function_exists('ahsc_current_theme_is_fse_theme' ) ) {
	/**
	 * The function checks if the current theme is a Full Site Editing (FSE) theme in PHP.
	 *
	 * @return bool value indicating whether the current theme is a Full Site Editing (FSE) theme.
	 */
	function ahsc_current_theme_is_fse_theme() {
		if ( function_exists( 'wp_is_block_theme' ) ) {
			return (bool) wp_is_block_theme();
		}
		if ( function_exists( 'gutenberg_is_fse_theme' ) ) {
			return (bool) gutenberg_is_fse_theme();
		}
		return false;
	}
}

/**
 * Write a debug info in to file.
 *
 * @param  string $message the messagge.
 * @param  string $name the name of message.
 * @param  string $type the type of log debug|info|warning|error.
 * @return void
 */
function AHSC_log( $message, $name = '', $type = 'info' ) {

	if ( class_exists('\ArubaSPA\HiSpeedCache\Debug\Logger') ) {

		//die(var_export(Logger::$logger_ready,true));
		switch ( $type ) {
			case 'debug':
				Logger::debug( $message, $name );
				break;
			case 'info':
				Logger::info( $message, $name );
				break;
			case 'warning':
				Logger::warning( $message, $name );
				break;
			case 'error':
				Logger::error( $message, $name );
				break;
		}
	}
}

/** Let's get rid of the annoying Site Health warning about no auto-updates. We don't want autoupdates because we want to do backups before updates!
 */

add_filter('site_status_tests', function (array $test_type) {

	unset($test_type['async']['background_updates']); // remove warning about Automatic background updates

	return $test_type;
}, 10, 1);

if ( ! \function_exists('ahsc_check_debug_status' ) ) {

	function ahsc_check_debug_status(){
		$ahsc_debug_status=false;
		$wpc_transformer = new HASC_WPCT(  ABSPATH . 'wp-config.php', true );
		$ahsc_db = false;
		$ahsc_dbl = false;
		if ($wpc_transformer->exists( 'constant', 'WP_DEBUG' )) {
			$ahsc_db = filter_var($wpc_transformer->get_value('constant','WP_DEBUG'), FILTER_VALIDATE_BOOLEAN);
		}
		if ($wpc_transformer->exists( 'constant', 'WP_DEBUG_LOG' )) {
			$ahsc_dbl = filter_var($wpc_transformer->get_value('constant','WP_DEBUG_LOG'), FILTER_VALIDATE_BOOLEAN);
		}
		if($ahsc_db || $ahsc_dbl ){
			$ahsc_debug_status=true;

		}
		return $ahsc_debug_status;
	}

}
