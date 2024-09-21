<?php


if(isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_cache_warmer']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_cache_warmer']){
    \add_action( 'wp_ajax_ahcs_cache_warmer',  'ahsc_cache_warmer_ajax_action' , 100 );
    \add_action( 'wp_ajax_nopriv_ahcs_cache_warmer', 'ahsc_cache_warmer_ajax_action' , 100 );
    $do_purge = ahsc_has_transient( 'ahsc_do_cache_warmer' );
    //var_dump($do_purge);
    if ( $do_purge ) {
        \add_action( 'init', 'ahsc_do_cache_warmer');
    }
}
function ahsc_do_cache_warmer(){
    if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_cache_warmer']){
        \add_action('admin_footer','ahsc_cache_warmer_runner' );
        \add_action('wp_footer', 'ahsc_cache_warmer_runner');
    }
}

function ahsc_cache_warmer_runner() {

    $ajax_uri = \admin_url( 'admin-ajax.php' );
    $action   = 'ahcs_cache_warmer';
    $nonce    = \wp_create_nonce( 'ahsc-cache-warmer' );

    $js_runner = <<<EOF
<script>
	( function() {
		const data = new FormData();
		data.append("action", "$action");
		data.append("ahsc_cw_nonce", "$nonce" );

		fetch( "$ajax_uri", {method: "POST",
			credentials: "same-origin",
			body: data}
		).then( r => r.json() ).then( rr => console.log('Cache Rigenerata') );
	}());
</script>
EOF;
    print($js_runner);//@phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ahsc_delete_transient('ahsc_do_cache_warmer');
}
/**
 * Medoto connected to WP's ajax handler to handle calls to cleaning APIs.
 *
 * @return void
 *
 * @SuppressWarnings(PHPMD.ElseExpression)
 */
function ahsc_cache_warmer_ajax_action() {
    $do_warmer = array();

    if ( isset( $_POST['ahsc_cw_nonce'] ) && ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['ahsc_cw_nonce'] ) ), 'ahsc-cache-warmer' ) ) {

        wp_die( wp_json_encode(AHSC_AJAX['security_error'] ) );
    }

    // If a static page has not been set as the site's home.
    if ( 'posts' === \get_option( 'show_on_front' )) {
        $do_warmer[] = \get_home_url( null, '/' );
    }

    // If a static page has been set as the site's home.
    if ( 'page' === get_option( 'show_on_front' ) ) {
        $do_warmer[] = \get_permalink( \get_option( 'page_on_front' ) );
        $blog_list = \get_option( 'page_for_posts' );

        // I check whether the two urls are different. If no page is set as 'article page', the same url is returned.
        if ( '0' != $blog_list ) {
            $do_warmer[] = \get_post_type_archive_link( 'post' ) ;
        }
    }

    if( class_exists( 'woocommerce' ) ) {
        $do_warmer[] = get_permalink( wc_get_page_id( 'shop' ) );
    }

    $recent_posts = wp_get_recent_posts(array(
        'numberposts' => 10, // Number of recent posts
        'post_status' => 'publish' // Get only the published posts
    ));

    foreach ($recent_posts as $recent_post) {
        $do_warmer[] = get_permalink( $recent_post['ID'] );
    }

    foreach ( $do_warmer as $warmer_item ) {
        \wp_remote_get( $warmer_item );
    }

    wp_die( wp_json_encode( array('esit' => true, 'items' => $do_warmer) ) );

}