<?php
/**
 * Manages the uninstallation process of the
 * Aruba HiSpeed Cache Plugin for more information
 * php version 7.4
 *
 * @category Wordpress-plugin
 * @package  Aruba-HiSpeed-Cache
 * @author   Aruba Developer <hispeedcache.developer@aruba.it>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
 */

namespace ArubaSPA\HiSpeedCache;

// If uninstall.php is not called by WordPress, die.
if ( ! \defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

\delete_site_option( 'aruba_hispeed_cache_options' );
\delete_site_option( 'aruba_hispeed_cache_version');

// Leftover of the cache warmer, a feature dropped in 3.1.0.
\delete_option( 'ahsc_do_cache_warmer' );
if ( is_multisite() ) {
	\delete_site_option( 'ahsc_do_cache_warmer' );
}

// Drop-in bookkeeping: the installed revision, the failed-sync back-off and the last status
// written to the log. Spelled out rather than calling ahsc_dropin_forget_version(), because
// WordPress runs this file on its own without loading the plugin. Keep in step with
// src/AHSC_Dropin.php.
\delete_site_option( 'ahsc_dropin_version' );
\delete_site_option( 'ahsc_dropin_sync_retry' );
\delete_site_option( 'ahsc_dropin_status' );

// Timestamp of the fallback transient sweep. Blog-scoped, not a site option, because the
// sweep it guards works on this blog's own options table.
\delete_option( 'ahsc_transient_sweep' );

// The .htaccess rules are single-site only; a network install never wrote them.
if ( ! is_multisite() ) {

	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	$ahsc_htaccess = get_home_path() . '.htaccess';
	insert_with_markers( $ahsc_htaccess, 'AHSC_RULES', array(' ') );
}

/*
 * The drop-in, on the other hand, is installed on multisite too — it lives in wp-content,
 * not per blog — so this deliberately runs outside the block above. It used to be inside,
 * which left a network install running an orphaned object cache after the plugin was gone.
 *
 * The ownership test is spelled out rather than calling ahsc_dropin_inspect(), because
 * WordPress runs this file without loading the plugin. It has to stay in step with
 * src/AHSC_Dropin.php: the marker, and the pre-marker signature that identifies our own
 * older revisions. Anything else belongs to another plugin — deleting it would destroy a
 * working object cache on the way out, which is the whole point of this check.
 */
$ahsc_file = WP_CONTENT_DIR . '/object-cache.php';

if ( file_exists( $ahsc_file ) && is_readable( $ahsc_file ) ) {
	$ahsc_dropin = (string) file_get_contents( $ahsc_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- WP_Filesystem is not available here.

	$ahsc_is_ours = false !== strpos( $ahsc_dropin, 'AHSC-Dropin-Version' ) ||
		( false !== strpos( $ahsc_dropin, 'WP_APC_KEY_SALT' ) &&
		  false !== strpos( $ahsc_dropin, 'APC_Object_Cache' ) );

	if ( $ahsc_is_ours ) {
		wp_delete_file( $ahsc_file );
	}
}