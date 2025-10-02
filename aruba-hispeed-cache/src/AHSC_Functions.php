<?php

use ArubaSPA\HiSpeedCache\Debug\Logger;

/**
  * operation at plugin activation
 * @return void
 */
function AHSC_activation( ) {
	// Get the option.
	$check=AHSC_check();
	if(!$check['is_aruba_server']){
		AHSC_deactivate_me();
	    wp_die( ahsc_get_check_notice($check), 'Aruba HiSpeed Cache dependency check', array( 'back_link' => true ) );
	}else{
	  $options = AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'];
	  if ( ! $options ) {
		  $options = array_map(
			  function( $opt ) {
				return $opt['default'];
			  },
			AHSC_OPTIONS_LIST_DEFAULT
		  );
	  }
	  \update_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'] , $options );
	  \update_site_option( 'aruba_hispeed_cache_version', AHSC_CONSTANT['ARUBA_HISPEED_CACHE_VERSION']);

		AHSC_remove_htaccess();
		if(array_key_exists('ahsc_static_cache',AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'])){
			if(isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_static_cache'])){
				if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_static_cache']){
					AHSC_edit_htaccess();
				}
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
	\delete_site_option( 'aruba_hispeed_cache_version');
	AHSC_remove_htaccess();
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
 * create link in plugin page
 * @return void
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


if ( ! \function_exists( 'ahsc_save_options' ) ) {
	/**
	 * Save plugin options
	 *
	 * @return void
	 */
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
							$new_options[ $opt_key ]= $_POST[ $opt_key ];
						}else{
							//var_dump("non setto time ");
							$wpc_transformer->remove('constant', 'WP_CRON_LOCK_TIMEOUT');
						}
					}elseif($opt_key!=="ahsc_dns_preconnect_domains"){
						$new_options[ $opt_key ] = ( isset( $_POST[ $opt_key ] ) ) ? true : false; 
					}else{
						if(isset( $_POST[ $opt_key ] ) ){
							$trans_domain_list = explode( "\n", trim( $_POST[ $opt_key ] ) );
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


if ( ! \function_exists( 'ahsc_reset_options' ) ) {
	/**
	 * Reset plugin options
	 *
	 * @return void
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
					// phpcs:ignore
					@unlink( WP_CONTENT_DIR . '/object-cache.php' );
			}

		    \update_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'], $new_options );

			$content = \esc_html( __('Original settings restored.', 'aruba-hispeed-cache') );
			return $content;

	}
}


if ( ! \function_exists( 'ahsc_has_transient' ) ) {

	/**
	 * It checks whether a transinet exists if yes, it returns the value otherwise it returns false.
	 *
	 * @param  string $transient .
	 * @return mixed
	 */
	function ahsc_has_transient( $transient ) {
		$temp=$GLOBALS['_wp_using_ext_object_cache'];
		$GLOBALS['_wp_using_ext_object_cache'] = false;
		$transient_value = ( \is_multisite() ) ? \get_site_transient( (string) $transient ) : \get_transient( (string) $transient );
		$_value=( false !== $transient_value ) ? $transient_value : false;;
		$GLOBALS['_wp_using_ext_object_cache'] = $temp;
		return $_value;
	}
}

if ( ! \function_exists( 'ahsc_set_transient' ) ) {

	/**
	 * Undocumented function
	 *
	 * @param  string  $transient .
	 * @param  mixed   $value .
	 * @param  integer $expiration .
	 * @return bool
	 */
	function ahsc_set_transient( $transient, $value, $expiration = 0 ) {
		$temp=$GLOBALS['_wp_using_ext_object_cache'];
		$GLOBALS['_wp_using_ext_object_cache'] = false;
		$_value=( \is_multisite() ) ?
			\set_site_transient( $transient, $value, $expiration ) :
			\set_transient( $transient, $value, $expiration );
		$GLOBALS['_wp_using_ext_object_cache'] = $temp;
		return $_value;
	}
}

if ( ! \function_exists( 'ahsc_delete_transient' ) ) {

	/**
	 * Undocumented function
	 *
	 * @param  string $transient .
	 * @return bool
	 */
	function ahsc_delete_transient( $transient ) {
		$temp=$GLOBALS['_wp_using_ext_object_cache'];
		$GLOBALS['_wp_using_ext_object_cache'] = false;
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			AHSC_log( 'hook::transient::delete', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}
		$_value=( \is_multisite() ) ?
			\delete_site_transient( $transient ) :
			\delete_transient( $transient );
		$GLOBALS['_wp_using_ext_object_cache'] = $temp;
		return $_value;
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
			if ( \is_multisite() ) {
				\delete_site_transient(AHSC_CHECKER['transient_name'] );
				return false;
			}

			\delete_transient( AHSC_CHECKER['transient_name']);
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