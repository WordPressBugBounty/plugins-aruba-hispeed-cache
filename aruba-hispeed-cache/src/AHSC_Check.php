<?php
const ACTIVE        = 'active';
const AVAILABLE     = 'available';
const UNAVAILABLE   = 'unavailable';
const NOARUBASERVER = 'no-aruba-server';

$target=AHSC_CONSTANT['HOME_URL'];
//var_dump(AHSC_CONSTANT);
$check_error=false;
$check_status_code=false;
$check_parameters=array(
	'is_aruba_server'=>"",
	'service_is_active'=>"",
	'service_is_activabile'=>"",
	'service_status'=>"",
	'esit'=>""
);

/**
 * Set_parameters_to_check Set the parameters to perform the service check.
 *
 * @param  boolean $is_aruba_server Set to true if the site is on aruba server.
 * @param  boolean $service_is_active Set to true if the service is active.
 * @param  boolean $service_is_activabile Set to true if the service is activabile.
 * @param  string  $service_status The value of x-aruba-cache header def is ''.
 * @return void
 */
function AHSC_set_parameters_to_check(
	$is_aruba_server,
	$service_is_active,
	$service_is_activabile,
	$service_status = ''
) {
	global $check_parameters;
	$check_parameters['is_aruba_server']       = $is_aruba_server;
	$check_parameters['service_is_active' ]    = $service_is_active;
	$check_parameters['service_is_activabile'] = $service_is_activabile;
	$check_parameters['service_status']        = $service_status;
}

/**
 * Headers_analizer - Analyze the request headers and value the variables.
 *
 * @since  1.0.1
 * @return void
 */
function AHSC_headers_analizer() {
	$headers=AHSC_get_headers();
	/**
	 * If the request headers are empty or the request
	 * produced a wp_error then I set everything to true.
	 */
	if ( empty( $headers ) ) {
		AHSC_set_parameters_to_check( false, false, false );
	}

	/**
	 * If the headers contain 'x-aruba-cache' we are on an aruba server.
	 * If it has value NA we are on servers without cache.
	 */
	if ( is_array($headers) && array_key_exists( 'x-aruba-cache', $headers ) ) {
		$is_active     = true;
		$is_activabile = true;
		if ( 'NA' === $headers['x-aruba-cache'] ) {
			$is_activabile = false;
			$is_active     = false;
		}
		AHSC_set_parameters_to_check( true, $is_active, $is_activabile, $headers['x-aruba-cache'] );

	}else{
		AHSC_set_parameters_to_check( false, false, false );

	}

	/**
	 * If the headers do not contain 'x-aruba-cache'
	 * we are not on the aruba server.
	 *
	 * If the 'server' header contains 'aruba-proxy'
	 * the service can be activated.
	 *
	 * If it is different from 'aruba-proxy' we are
	 * not on aruba server or behind cdn.
	 */
	if (  is_array($headers) && array_key_exists( 'server', $headers ) ) {
		switch ( $headers['server'] ) {
			case 'aruba-proxy':
				if ( is_array($headers) && array_key_exists( 'x-aruba-cache', $headers ) ){
					if( 'NA' !== $headers['x-aruba-cache'] ){
						$_status=$headers['x-aruba-cache'];
					}else{
						$_status=UNAVAILABLE;
					}
			    }
				AHSC_set_parameters_to_check( true, true, true , $_status);
				break;
			default:
				AHSC_set_parameters_to_check( false, false, false );

				if ( array_key_exists( 'x-servername', $headers ) && str_contains( $headers['x-servername'], 'aruba.it' ) ) {
					AHSC_set_parameters_to_check( true, false, true );
				}
				break;
		}
	}
}

/**
 * Check - Check the populated variables and issue a control message.
 *
 * @since  1.0.1
 * @return array
 */
 function AHSC_check() {
	global $check_parameters;
	AHSC_get_headers();
	AHSC_headers_analizer();

	$check_parameters['esit'] = ACTIVE;

	if ( $check_parameters['is_aruba_server'] && ! $check_parameters['service_is_active'] ) {
		$check_parameters['esit'] = ( $check_parameters['service_is_activabile'] ) ? ACTIVE : UNAVAILABLE;

	}else{
		$check_parameters['esit'] = ( $check_parameters['service_is_activabile'] ) ? ACTIVE : NOARUBASERVER;
	}

	//var_dump($check_parameters);

	return $check_parameters;
}

/**
 * Debugger - It exposes some elements of the control to
 * try to resolve any errors. To activate it, just go to the
 * dominio.tld/wp-admin/options-general.php?page=aruba-hispeed-cache&debug=1
 *
 * @return string
 */
 function debugger() {
	global $check_error,$check_status_code;

	$headers=AHSC_get_headers();

	 $check_parameters=AHSC_check();

	$data = array(
		'date'         => \wp_date( 'D, d M Y H:i:s', time() ),
		'target'       => AHSC_CONSTANT['HOME_URL'],
		'headers'      => $headers,
		'status_code'  => $check_status_code,
		'check_params' => $check_parameters,
	);

	if ( $check_error ) {
		$data['error'] = 'Sorry but the call was answered with an error. please try again later';
		unset( $data['headers'] );
		unset( $data['status_code'] );
		unset( $data['check_params'] );
	}

	return var_export( $data, true ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
}

/**
 * Get_headers - Getter of the headers for the request to perform the check.
 *
 * @since  1.0.1
 * @return bool
 */
function AHSC_get_headers() {

	global $check_status_code,$check_error;


	$response = \wp_remote_get(
		AHSC_CONSTANT['HOME_URL'],
		array(
			'sslverify'   => false,
			'user-agent'  => 'aruba-ua',
			'httpversion' => '1.1',
			'timeout'     =>AHSC_CHECKER['request_timeout'],
		)
	);


	if ( \is_array( $response ) && ! \is_wp_error( $response ) ) {
		$headers     = $response['headers']->getAll();
		$check_status_code = $response['response']['code'];

		return $headers;
	}

	if ( \is_wp_error( $response ) ) {
		$check_error = true;
	}

	return false;
}

/**
 * Check_hispeed_cache_services - check if aruba hispeed cache service is activable or is a aruba server.
 *
 * @param  string $plugin The plugin fine relative path.
 * @return void
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
 function check_hispeed_cache_services( $plugin ) {
	if ( 'aruba-hispeed-cache/aruba-hispeed-cache.php' === $plugin ) {
		$check         = AHSC_check();

		if ( \is_multisite() ) {
			\set_site_transient(
				AHSC_CHECKER['transient_name'],
				$check,
				AHSC_CHECKER['transient_life_time']
			);
		}else{
            \set_transient(
                AHSC_CHECKER['transient_name'],
                $check,
                AHSC_CHECKER['transient_life_time']
            );
        }
	}
}

if ( ! \function_exists( 'ahsc_get_check_notice' ) ) {

	/**
	 * Return null or the admin notice to render.
	 *
	 * @param  string $notice_type The option key to get.
	 * @return null|string
	 *
	 *  @SuppressWarnings(PHPMD.StaticAccess)
	 */
	function ahsc_get_check_notice( $notice_type ) {
		$localize_link = AHSC_LOCALIZE_LINK; // For php 5.6 compatibility.
		$notice = null;
		$lng=strtolower(substr( get_bloginfo ( 'language' ), 0, 2 ));

		if($notice_type){
			$notice_type=(array) $notice_type;
		switch ( $notice_type['esit'] ) {
			case AVAILABLE:
				$notice['handle']  = 'ahsc-service-warning';
				$notice['type']    = 'warning';
				$notice['message'] = \sprintf(
					\wp_kses(
					// translators: %1$s: the pca url.
					// translators: %2$s: the guide url.
						__( '<strong>The HiSpeed Cache service is not enabled.</strong> To activate it, go to your domain <a href="%1$s" rel="nofollow" target="_blank">control panel</a> (verifying the status may take up to 15 minutes). For further details <a href="%2$s" rel="nofollow" target="_blank">see our guide</a>.', 'aruba-hispeed-cache' ),
						array(
							'strong' => array(),
							'a'      => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					),
					esc_html( $localize_link['link_aruba_pca'][$lng] ),
					esc_html( $localize_link['link_guide'][$lng] )
				);
				break;
			case UNAVAILABLE:
				$notice['handle']  = 'ahsc-service-error';
				$notice['type']    = 'error';
				$notice['message'] = \sprintf(
					\wp_kses(
					// translators: %s: the assistance url.
						__( '<strong>The HiSpeed Cache service with which the plugin interfaces is not available on the server that hosts your website.</strong> To use HiSpeed Cache and the plugin, please contact <a href="%s" rel="nofollow" target="_blank">support</a>.', 'aruba-hispeed-cache' ),
						array(
							'strong' => array(),
							'a'      => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					),
					esc_html( $localize_link['link_assistance'][$lng] )
				);
				break;
			case NOARUBASERVER:
				$notice['handle']  = 'ahsc-service-error';
				$notice['type']    = 'error';
				$notice['message'] = \sprintf(
					\wp_kses(
					// translators: %s: the hosting truck url.
						__( '<strong>The Aruba HiSpeed Cache plugin cannot be used because your WordPress website is not hosted on an Aruba hosting platform.</strong> Buy an <a href="%s" rel="nofollow" target="_blank">Aruba Hosting service</a> and migrate your website to use the plugin.', 'aruba-hispeed-cache' ),
						array(
							'strong' => array(),
							'a'      => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					),
					esc_html( $localize_link[ 'link_hosting_truck' ][$lng] )
				);
				break;
		}

		if ( ACTIVE !== $notice_type['esit'] ) {
			$notice = AHSC_Notice_Render( $notice['handle'], $notice['type'],$notice['message'], true );
		}

		}else{
			$notice=null;
		}

		return $notice;
	}
}