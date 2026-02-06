<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if(isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_dns_preconnect'])
   && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_dns_preconnect']!=false) {

	function ahsc_dns_prefetch_to_preconnect( $urls, $relation_type ) {
		global $wp_scripts, $wp_styles;
		$unique_urls = array();

		foreach ( array( $wp_scripts, $wp_styles ) as $dependencies ) {
			if ( $dependencies instanceof WP_Dependencies && ! empty( $dependencies->queue ) ) {
				foreach ( $dependencies->queue as $handle ) {

					if ( ! isset( $dependencies->registered[ $handle ] ) ) {
						continue;
					}

					$dependency = $dependencies->registered[ $handle ];
					$parsed     = wp_parse_url( $dependency->src );

					if ( ! empty( $parsed['host'] ) && ! in_array( $parsed['host'], $unique_urls ) && isset($_SERVER['SERVER_NAME']) && $parsed['host'] !== $_SERVER['SERVER_NAME'] ) {
						if ( 'preconnect' === $relation_type ) {
							$unique_urls[] = array(
								'href' => $parsed['scheme'] . '://' . $parsed['host'],
								'crossorigin',
							);
						} else {
							$unique_urls[] = array(
								'href' => $parsed['scheme'] . '://' . $parsed['host'],
							);
						}
					}

				}
			}

			$site_option=get_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'] );
			$option       = ($site_option)?$site_option: AHSC_OPTIONS_LIST;
			if(isset($option['ahsc_dns_preconnect_domains']) && $option['ahsc_dns_preconnect_domains']!==""){
				foreach($option['ahsc_dns_preconnect_domains'] as $custom_domain){

				  $custom_domain_parsed=wp_parse_url( esc_url($custom_domain,array(
					  'https'
				  )) );
				  if(array_search($custom_domain,$unique_urls)===false && isset( $custom_domain_parsed['host'] ) && isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']!==$custom_domain_parsed['host']) {
					  if ( isset( $custom_domain_parsed['scheme'] ) && isset( $custom_domain_parsed['host'] ) ) {
					  
						// 	$unique_urls[] = array(
						// 	  'href' => $custom_domain_parsed['scheme'] . '://' . $custom_domain_parsed['host'],
						//   );
						
						// Use scheme and host coming from the original URL
						$scheme = $custom_domain_parsed['scheme'];
						$host   = $custom_domain_parsed['host'];
						// echo "<script>console.log('scheme:', " . json_encode($scheme) . ");</script>";

						// Added sample bug to simulate the issue
						// $bug_suffix = (rand(0,1) === 1) ? 'https' : 'http';
						// $href = $host . $bug_suffix;

						// If the host is malformed fix it
						$host_fixed = preg_replace('/http[s]?$/i', '', $host);

						// If something goes wrong and the host becomes empty, skip it
						if ( $host_fixed === '' ) {
							continue;
						}

						// Build href based on the type of hint
						if ('dns-prefetch' === $relation_type) {
							$href = '//' . $host_fixed;
						} else {
							$href = $scheme . '://' . $host_fixed;
						}						

						$unique_urls[] = array(
							'href' => $href,
						);

				     }
				  }
				}

			}
		}
		
		if ( 'preconnect' === $relation_type || 'dns-prefetch' === $relation_type) {
			$urls = $unique_urls;
		}
		return $urls;
	}
	if(!is_admin()){
	  add_filter( 'wp_resource_hints', 'ahsc_dns_prefetch_to_preconnect', 0, 2 );
	}
}else{
	remove_action('wp_head', 'wp_resource_hints', 2);
}