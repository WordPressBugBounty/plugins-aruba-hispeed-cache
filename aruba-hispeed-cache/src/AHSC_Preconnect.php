<?php

if(isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_dns_preconnect']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_dns_preconnect']) {

	function dns_prefetch_to_preconnect( $urls, $relation_type ) {
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

					if ( ! empty( $parsed['host'] ) && ! in_array( $parsed['host'], $unique_urls ) && $parsed['host'] !== $_SERVER['SERVER_NAME'] ) {
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
				  if(array_search($custom_domain,$unique_urls)===false && isset( $custom_domain_parsed['host'] ) && $_SERVER['HTTP_HOST']!==$custom_domain_parsed['host']) {
					  if ( isset( $custom_domain_parsed['scheme'] ) && isset( $custom_domain_parsed['host'] ) ) {
					  $unique_urls[] = array(
						  'href' => $custom_domain_parsed['scheme'] . '://' . $custom_domain_parsed['host'],
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
	  add_filter( 'wp_resource_hints', 'dns_prefetch_to_preconnect', 0, 2 );
	}
}else{
	remove_action('wp_head', 'wp_resource_hints', 2);
}