<?php
include "pages/AHSC_Settings.php";
/** Add menu setting page for plugin*/
add_action('admin_menu', 'AHSC_Admin_Menu');// menù admin
/** Plugin Menu link */
function AHSC_Admin_Menu() {

	\add_submenu_page(
		( ! is_multisite() ) ? 'options-general.php' : 'settings.php',
		__( 'Aruba HiSpeed Cache', 'aruba-hispeed-cache' ),
		__( 'Aruba HiSpeed Cache', 'aruba-hispeed-cache' ),
		'manage_options',
		'aruba-hispeed-cache',
		"AHSC_GeneralSetting"
	);
}
/** Settings Page */
function AHSC_GeneralSetting()
{
	$p = new AHSC_Settings();
	$p->buildPage();
}