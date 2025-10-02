<?php
if(isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_xmlrpc_status']) &&
   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_xmlrpc_status']!=="false" ) {
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter( 'pings_open', '__return_false' );
	add_filter( 'xmlrpc_methods', function( $methods ) { unset( $methods['pingback.ping'] ); return $methods; } );
	remove_action( 'wp_head', 'rsd_link' );
	function ahsc_disable_trackbacks_on_posts( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( in_array( $post_type, array( 'post', 'page' ) ) ) {
			update_post_meta( $post_id, '_ping_status', 'closed' );
		}
	}
	add_action( 'publish_post', 'ahsc_disable_trackbacks_on_posts' );
	add_action( 'publish_page', 'ahsc_disable_trackbacks_on_posts' );

	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
		exit;
}