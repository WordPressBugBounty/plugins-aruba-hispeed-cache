<?php


if(isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_cache_warmer']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_cache_warmer']){
    \add_action( 'wp_ajax_ahcs_cache_warmer',  'ahsc_cache_warmer_ajax_action' , 100 );
    \add_action( 'wp_ajax_nopriv_ahcs_cache_warmer', 'ahsc_cache_warmer_ajax_action' , 100 );

	$do_purge=get_option('ahsc_do_cache_warmer',false);
	//$do_purge = ahsc_has_transient( 'ahsc_do_cache_warmer' );
    //var_dump($do_purge);
     if($do_purge){
        \add_action( 'init', 'ahsc_do_cache_warmer');
	     //add_action( 'wp', 'ahsc_cache_warmer_ajax_action');
     }
}
function ahsc_do_cache_warmer(){
	$do_purge=get_option('ahsc_do_cache_warmer',false);
    if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_cache_warmer'] && $do_purge){
        \add_action('admin_footer','ahsc_cache_warmer_runner' );
        \add_action('wp_footer', 'ahsc_cache_warmer_runner');
    }
}

function ahsc_cache_warmer_runner() {
	$do_purge=get_option('ahsc_do_cache_warmer',false);
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
	if($do_purge){
      print($js_runner);//@phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	// ahsc_delete_transient('ahsc_do_cache_warmer');
	//update_option('ahsc_do_cache_warmer',false);
}
/**
 * Medoto connected to WP's ajax handler to handle calls to cleaning APIs.
 *
 * @return void
 *
 * @SuppressWarnings(PHPMD.ElseExpression)
 */
function ahsc_cache_warmer_ajax_action() {
	$do_purge=get_option('ahsc_do_cache_warmer',false);
	if($do_purge) {
		$do_warmer = array();

		if ( isset( $_POST['ahsc_cw_nonce'] ) && ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['ahsc_cw_nonce'] ) ), 'ahsc-cache-warmer' ) ) {

			wp_die( wp_json_encode( AHSC_AJAX['security_error'] ) );
		}

		// If a static page has not been set as the site's home.
		if ( 'posts' === \get_option( 'show_on_front' ) ) {
			$do_warmer[] = \get_home_url( null, '/' );
		}

		// If a static page has been set as the site's home.
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$do_warmer[] = \get_permalink( \get_option( 'page_on_front' ) );
			$blog_list   = \get_option( 'page_for_posts' );

			// I check whether the two urls are different. If no page is set as 'article page', the same url is returned.
			if ( '0' != $blog_list ) {
				$do_warmer[] = \get_post_type_archive_link( 'post' );
			}
		}

		if ( class_exists( 'woocommerce' ) ) {
			$do_warmer[] = get_permalink( wc_get_page_id( 'shop' ) );
		}


		$recent_posts = wp_get_recent_posts( array(
			'numberposts' => 10, // Number of recent posts
			'post_status' => 'publish' // Get only the published posts
		) );

		foreach ( $recent_posts as $recent_post ) {
			$do_warmer[] = get_permalink( $recent_post['ID'] );
		}

		//prodotti ultimi 10 prodotti modificati
		if ( class_exists( 'woocommerce' ) ) {
			$args     = array(
				'limit'   => 10,
				'orderby' => 'modified',
				'order'   => 'DESC',
				'return'  => 'ids',
			);
			$products = wc_get_products( $args );
			foreach ( $products as $pos => $pid ) {
				$do_warmer[] = get_permalink( $pid );
			}
		}

		//pagine linkate nella homepage

		$url  = get_home_url();
		//$html = file_get_contents( $url );
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$html = curl_exec($ch);
		curl_close($ch);
		$doc  = new DOMDocument();
		$doc->loadHTML( $html );
		$xpath   = new DOMXpath( $doc );
		$nodes   = $xpath->query( '//a' );
		$_domain = preg_replace( '/^www\./', '', $_SERVER['HTTP_HOST'] );
		foreach ( $nodes as $node ) {
			$domain = implode( '.', array_slice( explode( '.', parse_url( $node->getAttribute( 'href' ), PHP_URL_HOST ) ), - 2 ) );
			if ( $domain == $_domain && array_search( trailingslashit( $node->getAttribute( 'href' ) ), $do_warmer ) === false ) {
				if ( $node->getAttribute( 'href' ) !== $url ) {
					$do_warmer[] = $node->getAttribute( 'href' );
				}
			}

		}

		$do_warmer = array_unique( $do_warmer );

		foreach ( $do_warmer as $warmer_item ) {
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $warmer_item );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
			curl_setopt( $ch, CURLINFO_HEADER_OUT, false );
			curl_setopt( $ch, CURLOPT_VERBOSE, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "User-Agent: arubacache" ) );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "accept-encoding: gzip, deflate, br, zstd" ) );
			try {
				curl_exec( $ch );
			} catch ( \Exception $exceptiongeneral ) {
				file_put_contents( 'php://stderr', $exceptiongeneral . "\n" );
			}
			curl_close( $ch );
		}

		update_option( 'ahsc_do_cache_warmer', false );
		wp_die( wp_json_encode( array( 'esit' => true, 'items' => $do_warmer ) ) );
	}else{
		wp_die( wp_json_encode( array( 'esit' => true, 'items' => 'no cache to warming' ) ) );
	}
}