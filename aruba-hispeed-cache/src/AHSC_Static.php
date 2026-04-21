<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//die(var_dump(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']));
if ( is_array( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'] ) && array_key_exists( 'ahsc_static_cache', AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'] ) ) {
	add_action( 'updated_option', 'AHSC_handle_static_cache_option_update', 10, 3 );
}
//$AHSC_marker = 'AHSC_RULES';

/**
 * Sync directives in .htaccess file when the option changes
 */
function AHSC_handle_static_cache_option_update( $option, $old_value, $value ) {
	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'] !== $option ) {
		return;
	}

	if ( ! is_array( $value ) ) {
		return;
	}

	$old_status = false;
	if ( is_array( $old_value ) && isset( $old_value['ahsc_static_cache'] ) ) {
		$old_status = (bool) $old_value['ahsc_static_cache'];
	}

	$new_status = false;
	if ( isset( $value['ahsc_static_cache'] ) ) {
		$new_status = (bool) $value['ahsc_static_cache'];
	}

	if ( $old_status === $new_status ) {
		return;
	}

	if ( $new_status ) {
		AHSC_edit_htaccess();
	} else {
		AHSC_remove_htaccess();
	}
}

/**
 * Get directives for .htaccess file
 */
function AHSC_get_htaccess_rules() {
	return array(
		'<IfModule mod_expires.c>',
		'ExpiresActive on',
		'# CSS',
		'ExpiresByType text/css "access plus 1 year"',
		'# Data interchange',
		'ExpiresByType application/json "access plus 0 seconds"',
		'ExpiresByType application/xml "access plus 0 seconds"',
		'ExpiresByType text/xml "access plus 0 seconds"',
		'# Favicon (cannot be renamed!)',
		'ExpiresByType image/x-icon "access plus 1 week"',
		'# HTML components (HTCs)',
		'ExpiresByType text/x-component "access plus 1 month"',
		'# JavaScript',
		'ExpiresByType application/javascript "access plus 1 year"',
		'# Manifest files',
		'ExpiresByType application/x-web-app-manifest+json "access plus 0 seconds"',
		'ExpiresByType text/cache-manifest "access plus 0 seconds"',
		'# Media',
		'ExpiresByType audio/ogg "access plus 1 month"',
		'ExpiresByType image/gif "access plus 1 month"',
		'ExpiresByType image/jpeg "access plus 1 month"',
		'ExpiresByType image/png "access plus 1 month"',
		'ExpiresByType image/webp "access plus 1 month"',
		'ExpiresByType video/mp4 "access plus 1 month"',
		'ExpiresByType video/ogg "access plus 1 month"',
		'ExpiresByType video/webm "access plus 1 month"',
		'# Web feeds',
		'ExpiresByType application/atom+xml "access plus 1 hour"',
		'ExpiresByType application/rss+xml "access plus 1 hour"',
		'# Web fonts',
		'ExpiresByType application/font-woff2 "access plus 1 month"',
		'ExpiresByType application/font-woff "access plus 1 month"',
		'ExpiresByType application/vnd.ms-fontobject "access plus 1 month"',
		'ExpiresByType application/x-font-ttf "access plus 1 month"',
		'ExpiresByType font/opentype "access plus 1 month"',
		'ExpiresByType image/svg+xml "access plus 1 month"',
		'</IfModule>',
		'# Set the cache-control max-age',
		'# 1 year',
		'<FilesMatch ".(ico|pdf|flv|jpg|jpeg|png|gif|js|css|swf|webp|woff2|woff|ttf)$">',
		'Header set Cache-Control "max-age=31449600, public"',
		'</FilesMatch>',
		'# 2 DAYS',
		'<FilesMatch ".(xml|txt)$">',
		'Header set Cache-Control "max-age=172800, public, must-revalidate"',
		'</FilesMatch>',
		'<IfModule mod_deflate.c>',
		'# Compress HTML, CSS, JavaScript, Text, XML and fonts',
		'AddOutputFilterByType DEFLATE application/javascript',
		'AddOutputFilterByType DEFLATE application/rss+xml',
		'AddOutputFilterByType DEFLATE application/vnd.ms-fontobject',
		'AddOutputFilterByType DEFLATE application/x-font',
		'AddOutputFilterByType DEFLATE application/x-font-opentype',
		'AddOutputFilterByType DEFLATE application/x-font-otf',
		'AddOutputFilterByType DEFLATE application/x-font-truetype',
		'AddOutputFilterByType DEFLATE application/x-font-ttf',
		'AddOutputFilterByType DEFLATE application/x-javascript',
		'AddOutputFilterByType DEFLATE application/xhtml+xml',
		'AddOutputFilterByType DEFLATE application/xml',
		'AddOutputFilterByType DEFLATE font/opentype',
		'AddOutputFilterByType DEFLATE font/otf',
		'AddOutputFilterByType DEFLATE font/ttf',
		'AddOutputFilterByType DEFLATE image/svg+xml',
		'AddOutputFilterByType DEFLATE image/x-icon',
		'AddOutputFilterByType DEFLATE text/css',
		'AddOutputFilterByType DEFLATE text/html',
		'AddOutputFilterByType DEFLATE text/javascript',
		'AddOutputFilterByType DEFLATE text/plain',
		'AddOutputFilterByType DEFLATE text/xml',
		'# Remove browser bugs (only needed for really old browsers)',
		'BrowserMatch ^Mozilla/4 gzip-only-text/html',
		'BrowserMatch ^Mozilla/4\.0[678] no-gzip',
		'BrowserMatch \bMSIE !no-gzip !gzip-only-text/html',
		//'Header append Vary User-Agent',
		'</IfModule>',
	);
}

/**
 * ADD directives from .htaccess file
 */
function AHSC_edit_htaccess() {
	if ( ! is_multisite() ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		$AHSC_marker = 'AHSC_RULES';
		$home_path   = get_home_path();
		$htaccess_location = $home_path . '.htaccess';
		$filesystem = new WP_Filesystem_Direct( true );

		$str  = "# BEGIN $AHSC_marker\n ".implode("\n",AHSC_get_htaccess_rules())."\n# END $AHSC_marker\n";
		$str .= $filesystem->get_contents($htaccess_location);
		$filesystem->put_contents($htaccess_location,$str,0644);
	}
}

/**
 * REMOVE directives from .htaccess file
 */
function AHSC_remove_htaccess() {
	if ( ! is_multisite() ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		$AHSC_marker = 'AHSC_RULES';
		$htaccess_location = get_home_path() . '.htaccess';
		$filesystem = new WP_Filesystem_Direct( true );
		$content=  $filesystem->get_contents($htaccess_location);
		// Rimuovi tutto tra i marker (inclusi i marker stessi)
		$pattern = '/# BEGIN '.$AHSC_marker.'.*?# END '.$AHSC_marker.'\s*/s';
		$newContent = preg_replace($pattern, '', $content);
		$filesystem->put_contents($htaccess_location,$newContent,0644);
	}
}