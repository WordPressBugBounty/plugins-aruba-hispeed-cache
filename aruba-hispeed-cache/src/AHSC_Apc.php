<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_action("wp_ajax_ahsc_check_apc_file", "ahsc_check_apc_file");
//add_action("wp_ajax_nopriv_ahsc_check_apc_file", "ahsc_check_apc_file");

function ahsc_check_apc_file() {
	$target = WP_CONTENT_DIR . '/object-cache.php';
	$result=array();

	if (file_exists( $target )) {
		$result['message']=AHSC_Notice_Render('ahsc-service-error', 'error',\wp_kses(
			__( '<strong>Another plugin use object cache.</strong> Deactivate the plugin or functionality and retry.', 'aruba-hispeed-cache' ),
			array(
				'strong' => array(),
			)
		), true );
		$result['result']= false;
	}else {
		$result['result']= true;
	}

	echo wp_json_encode($result);
	die();
}

add_action("wp_ajax_ahsc_create_apc_file", "ahsc_create_apc_file");
//add_action("wp_ajax_nopriv_ahsc_create_apc_file", "ahsc_create_apc_file");
function ahsc_create_apc_file(){
	if(current_user_can( 'manage_options' ) && isset($_POST['ahsc_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash( $_POST['ahsc_nonce'])), 'ahsc-purge-cache' )) {
		$result = array();
		$target = WP_CONTENT_DIR . '/object-cache.php';
		$source = __DIR__ . '/APC/object-cache.php';

		/*
		 * Was copy() followed by a separate chmod( $target, 0644 ). WP_Filesystem::copy()
		 * does both in one call — the fourth argument is the mode — and works on hosts
		 * where PHP cannot write directly and WordPress falls back to FTP or SSH. Where
		 * copy() used to succeed, get_filesystem_method() selects the "direct" transport,
		 * which is a plain copy() plus chmod, so nothing changes. The third argument is
		 * true because PHP's copy() overwrites by default and WP_Filesystem's does not.
		 */
		require_once ABSPATH . 'wp-admin/includes/file.php';
		global $wp_filesystem;

		$is_copied = false;

		if ( WP_Filesystem() && $wp_filesystem instanceof WP_Filesystem_Base ) {
			$is_copied = $wp_filesystem->copy( $source, $target, true, FS_CHMOD_FILE );
		} else {
			AHSC_log( 'Could not initialise WP_Filesystem, the object-cache.php drop-in was not installed.', 'apcu', 'warning' );
		}

		if ( ! $is_copied ) {
			AHSC_log( sprintf( 'Could not install the object-cache.php drop-in into %s.', WP_CONTENT_DIR ), 'apcu', 'warning' );
		}

		/*
		 * Was hard-coded to true regardless of the outcome: a failed copy still answered
		 * "ok", the interface ticked the checkbox and the follow-up call switched the
		 * ahsc_apc option on for a drop-in that had never been written.
		 */
		$result['result'] = (bool) $is_copied;
		echo wp_json_encode( $result );
	}
	die();
}
add_action("wp_ajax_ahsc_update_apc_Settings", "ahsc_update_apc_Settings");
//add_action("wp_ajax_nopriv_ahsc_update_apc_Settings", "ahsc_update_apc_Settings");
function ahsc_update_apc_Settings() {
	if(current_user_can( 'manage_options' ) && isset($_POST['ahsc_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash( $_POST['ahsc_nonce'])), 'ahsc-purge-cache' )) {
		$result            = array();
		$c_opt             = AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'];
		$c_opt['ahsc_apc'] = true;
		update_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'], $c_opt );
		$result['result'] = true;
		echo wp_json_encode( $result );
	}
	die();
}


add_action("wp_ajax_ahsc_delete_apc_file", "ahsc_delete_apc_file");
//add_action("wp_ajax_nopriv_ahsc_delete_apc_file", "ahsc_delete_apc_file");
function ahsc_delete_apc_file(){
	if(current_user_can( 'manage_options' ) && isset($_POST['ahsc_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash( $_POST['ahsc_nonce'])), 'ahsc-purge-cache' )) {
		$result = array();
		$file   = WP_CONTENT_DIR . '/object-cache.php';
		$c_opt  = AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'];
		if ( file_exists( $file ) ) {
			\wp_delete_file( $file );
			$result['result'] = true;

		}
		//$c_opt=get_site_option(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME']);
		$c_opt['ahsc_apc'] = false;
		update_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'], $c_opt );

		echo wp_json_encode( $result );
	}
	die();
}