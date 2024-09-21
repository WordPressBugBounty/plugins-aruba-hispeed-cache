<?php
if(isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_xmlrpc_status']) &&
   !AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_xmlrpc_status']) {

	add_filter( 'xmlrpc_enabled', '__return_false' );
	remove_action( 'wp_head', 'rsd_link' );
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
		exit;
}