<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$AHSC_check_version=true;
 function AHSC_required_wp_version() {
	global $wp_version,$AHSC_check_version;

	$wp_min_version = AHSC_REQUIREMENTS['minimum_wp'];

	if ( ! \version_compare( $wp_version, $wp_min_version, '>=' ) ) {

		$content = \sprintf(
		// translators: %s: the wp min required version.
			\esc_html__( 'Sorry, Aruba HiSpeed Cache requires WordPress %s or higher.', 'aruba-hispeed-cache' ),
			$wp_min_version
		);

		$wp_version_args=array( 'ahs_wp_version', $content, 'error' );

		\add_action( 'admin_notices',function() use ( $wp_version_args ) {
			AHSC_Notice_Render( $wp_version_args[0],$wp_version_args[2],$wp_version_args[1] ); } );
		\add_action( 'network_admin_notices',function() use ( $wp_version_args ) {
			AHSC_Notice_Render( $wp_version_args[0],$wp_version_args[2],$wp_version_args[1] ); });

		$AHSC_check_version = false;
	}
}

/**
 * Compares PHP versions and add admin_notice if it's not compatible
 *
 * @return void
 */
 function AHSC_required_php_version() {
	 global $AHSC_check_version;
	$php_min_version = AHSC_REQUIREMENTS['minimum_php'];

	if ( ! \version_compare( phpversion(), $php_min_version, '>=' ) ) {

		$content = \sprintf(
		// translators: %s: the min php version required.
			\esc_html__( 'Sorry, Aruba HiSpeed Cache requires PHP %s or higher.', 'aruba-hispeed-cache' ),
			$php_min_version
		);
		$php_version_args=array( 'ahs_wp_version', $content, 'error' );

		\add_action( 'admin_notices',function() use ( $php_version_args ) {
			AHSC_Notice_Render( $php_version_args[0],$php_version_args[2],$php_version_args[1] ); });
		\add_action( 'network_admin_notices',function() use ( $php_version_args ) {
			AHSC_Notice_Render( $php_version_args[0],$php_version_args[2],$php_version_args[1] ); } );

		$AHSC_check_version = false;
	}
}

function AHSC_check_requirement(){
	 global $AHSC_check_version;
	AHSC_required_wp_version();
	AHSC_required_php_version();

	if ( ! $AHSC_check_version ) {
		\add_action(
			'admin_init',
			function () {
				AHSC_deactivate_me();
			}
		);
	}
}

/*
 * AHSC_VERSION is hand-maintained alongside the plugin header and readme.txt, so nothing
 * stops a release bumping one and forgetting another — and the version is what busts the
 * browser cache on this plugin's own CSS and JS, so a stale value ships stale assets.
 * Reading the header to check is exactly the per-request cost item 8 removed, so the
 * comparison runs only in debug builds, and only in the admin.
 */
if ( AHSC_CORE['debug'] ) {
	\add_action( 'admin_init', 'AHSC_check_version_drift' );
}

/**
 * Warn when AHSC_VERSION and the plugin header have drifted apart.
 *
 * @return void
 */
function AHSC_check_version_drift() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	// Translation off on purpose: the value is only compared here, never displayed.
	$header = \get_plugin_data( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_FILE'], false, false );

	if ( ! empty( $header['Version'] ) && AHSC_VERSION !== $header['Version'] ) {
		AHSC_log(
			'AHSC_VERSION is ' . AHSC_VERSION . ' but the plugin header says ' . $header['Version'],
			'version drift',
			'warning'
		);
	}
}
