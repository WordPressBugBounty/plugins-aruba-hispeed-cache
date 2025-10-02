<?php
//var_dump( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc'] );
/*
if( isset( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc'] ) &&
     AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc'] ){

    $target = WP_CONTENT_DIR . '/object-cache.php';

	$source = __DIR__ . '/APC/object-cache.php';
	var_dump($source);
	if ( ! file_exists( $target ) ) {
		if ( file_exists( $source ) ) {
			// phpcs:ignore
			@copy( $source, $target );
			// phpcs:ignore
			@chmod( $target, 0644 );
		}
	}
}*/

add_action("wp_ajax_ahsc_check_apc_file", "ahsc_check_apc_file");
add_action("wp_ajax_nopriv_ahsc_check_apc_file", "ahsc_check_apc_file");

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
	$result = json_encode($result);
	echo $result;
	die();
}

add_action("wp_ajax_ahsc_create_apc_file", "ahsc_create_apc_file");
add_action("wp_ajax_nopriv_ahsc_create_apc_file", "ahsc_create_apc_file");
function ahsc_create_apc_file(){
	$result=array();
	$target = WP_CONTENT_DIR . '/object-cache.php';
	$source = __DIR__ . '/APC/object-cache.php';

	$is_copied=copy( $source, $target );
	if($is_copied){
	  chmod( $target, 0644 );
	}
	$result['result']= true;

	$_result = json_encode($result);
	echo $_result;
	die();
}
add_action("wp_ajax_ahsc_update_apc_Settings", "ahsc_update_apc_Settings");
add_action("wp_ajax_nopriv_ahsc_update_apc_Settings", "ahsc_update_apc_Settings");
function ahsc_update_apc_Settings() {
	$result=array();
	$c_opt=AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'];
	$c_opt['ahsc_apc']=true;
	update_site_option(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'], $c_opt);
	$result['result']= true;

	$_result = json_encode($result);
	echo $_result;
	die();
}


add_action("wp_ajax_ahsc_delete_apc_file", "ahsc_delete_apc_file");
add_action("wp_ajax_nopriv_ahsc_delete_apc_file", "ahsc_delete_apc_file");
function ahsc_delete_apc_file(){
	$result=array();
	$file = WP_CONTENT_DIR . '/object-cache.php';
	$c_opt=AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'];
	if ( file_exists( $file ) ) {
		// phpcs:ignore
		@unlink( $file );
		$result['result']= true;

	}
	//$c_opt=get_site_option(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME']);
	$c_opt['ahsc_apc']=false;
	update_site_option(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'], $c_opt);

	$_result = json_encode($result);
	echo $_result;
	die();
}