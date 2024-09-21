<?php
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