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

		$is_copied = copy( $source, $target );
		if ( $is_copied ) {
			chmod( $target, 0644 );//@phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		}
		$result['result'] = true;
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
			// phpcs:ignore
			@unlink( $file );
			$result['result'] = true;

		}
		//$c_opt=get_site_option(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME']);
		$c_opt['ahsc_apc'] = false;
		update_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'], $c_opt );

		echo wp_json_encode( $result );
	}
	die();
}