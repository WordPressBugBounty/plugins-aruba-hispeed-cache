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

		/*
		 * The copy itself lives in ahsc_dropin_install(), shared with the upgrade sync, so
		 * both paths get the same WP_Filesystem handling and the same staged-then-moved
		 * write. It used to be an inline WP_Filesystem::copy() here.
		 */
		$is_copied = ahsc_dropin_install();

		if ( $is_copied ) {
			// Record what was just written, so the per-request check in
			// ahsc_dropin_maybe_sync() has a baseline to compare against.
			ahsc_dropin_record_version();
		} else {
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
		$c_opt  = AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'];

		/*
		 * Reports success only when our drop-in is actually gone. A foreign drop-in is left
		 * alone and logged rather than deleted; see ahsc_dropin_remove(). The APCu setting
		 * is switched off either way: the admin asked for that, and leaving it on while
		 * another plugin owns the drop-in would be a lie.
		 */
		$result['result'] = ahsc_dropin_remove();
		//$c_opt=get_site_option(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME']);
		$c_opt['ahsc_apc'] = false;
		update_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'], $c_opt );

		echo wp_json_encode( $result );
	}
	die();
}