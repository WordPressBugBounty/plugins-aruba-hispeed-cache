=== Aruba HiSpeed Cache ===

Contributors: arubait, arubadev, arubasupport
Tags: Aruba, cache, performance, pagespeed, optimize
Tested up to: 6.8
Stable tag: 3.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Aruba HiSpeed Cache interfaces directly with an Aruba hosting platform's HiSpeed Cache service and automates its management.

== Description ==

**Aruba HiSpeed Cache** is a plugin that interfaces directly with the **HiSpeed Cache** service for an [Aruba](https://www.aruba.it/en/) [hosting platform](https://hosting.aruba.it/en/) and automates its management in the WordPress dashboard, without having to access the website's control panel.

**The plugin can only be used if your WordPress website is hosted on an [Aruba](https://www.aruba.it/en/) [hosting platform](https://hosting.aruba.it/en/).**

The HiSpeed Cache service significantly reduces the TTFB (first Byte transfer time) and webpage loading times.

When the service is active, the plugin lets you clear the cache automatically (and/or manually) every time a page or post is edited, without having to access the control panel for the website by clicking on the link provided.

HiSpeed Cache keeps dynamic content in the servers' memory after the first time it loads, making it available for subsequent requests much faster, significantly speeding up website browsing. The plugin simply clears the cache every time a custom page, article or content item is edited.

For more details and to find out whether the HiSpeed Cache service is active on your website [please refer to our guide](https://guide.hosting.aruba.it/hosting/wordpress-e-altri-cms/wordpress-plugin-aruba-hispeed-cache.aspx?viewmode=0#eng).

== Installation ==

= From your WordPress dashboard =
1. Visit 'Plugins -> Add New'
2. Search for 'Aruba HiSpeed Cache'
3. Activate Aruba HiSpeed Cache from your Plugins page.
4. Click 'Settings -> Aruba HiSpeed Cache'

= From WordPress.org =
1. Download Aruba HiSpeed Cache.
2. Create a directory named 'aruba-hispeed-cache' in your '/wp-content/plugins/' directory, using your preferred method (ftp, sftp, scp, etc.).
3. Activate Aruba HiSpeed Cache  from your Plugins page.
4. Click Options from your Plugins page

== Frequently Asked Questions ==

= What is HiSpeed Cache? =

HiSpeed Cache is a dynamic caching system that significantly improves webpage loading speeds. The active cache reduces the time to first byte (TTFB). The service also lets you clear the cache automatically or manually, whenever a page or any content is edited.

= What is the purpose of the plugin? =

When the service is active, using the plugin means you can clear the website's cache at any time directly from the WordPress dashboard, without having to access the hosting control panel. You can set the cache to clear automatically, or you can use the manual option.

= My website is not hosted on an Aruba hosting platform. Can I still use the plugin? =

You can install the plugin, but it was designed to interface with the caching system for an Aruba hosting platform. Purchase an Aruba hosting service then migrate your website to use the plugin.

= Should I exclude pages from the caching system? =

No, you don't need to because the caching system already excludes:

addresses with one of the following strings:
wp-login, preview=true, cart, my-account, checkout, addons, add-to-cart, wp-cron.php, xmlrpc.php, contact, task=registration, registerview=registration|administrator|remind|login, admin/content/backup_migrate/export, status.php, update.php, install.php, user, info, flag, ajax, aha;

requests with cookies containing one of the following strings:
wordpress_no_cache, comment_author, wordpress_logged_in_, yith_wcwl_products, wp-postpass_, it_exchange_session_, wp_woocommerce_session, woocommerce_cart_hash, edd_items_in_cart=1, jSGCacheBypass=1, wpSGCacheBypass=1, woocommerce_items_in_cart=1.

== Screenshots ==

1. General Settings Disabled
2. General Settings Enabled

== Changelog ==

= 3.0.0 =
* Frontend revisioned.
* Add object cache managment.
* Various fixes.
* Tested up 6.8

= 2.0.24 =
* Manage WP_CRON Timeout.
* Various minor bug fixes.

= 2.0.23 =
* Fix UserAgent for cache warming.

= 2.0.22 =
* Various minor bug fixes.

= 2.0.21 =
* Various minor bug fixes.

= 2.0.20 =
* Sheduled Post supported

= 2.0.19 =
* Add Optimize HTML code option
* Tested up 6.7
* Purge expired transients option

= 2.0.18 =
* Add optimize image loading option

= 2.0.17 =
* Add Cache management optimization for Elementor
* Add XML-RPC selector
* Various minor bug fixes.

= 2.0.16 =
* .htaccess rules bug fix.
* Add DNS-Prefetch and Preconnect for external resources.

= 2.0.15 =
* .htaccess rules bug fix.

= 2.0.14 =
* Various minor bug fixes.

= 2.0.13 =
* Various minor bug fixes.

= 2.0.12 =
* Various minor bug fixes.
* Tested up WordPress 6.6

= 2.0.11 =
* Various minor bug fixes.

= 2.0.10 =
* Various minor bug fixes.

= 2.0.9 =
* Code Review 
* Added Static Files Optimization
* Various Minor Bug Fixing

= 2.0.8 =
* Tested up WordPress 6.5

= 2.0.7 =
* Various minor bug fixes.
* The debugging and logging code base was modified to be plug-n-play. solved CVE-2023-44983, thanks Patchstack Alliance

= 2.0.6 =
* Various minor bug fixes.

= 2.0.5 =
* Fix CVE-2023-44983, thanks Joshua Chan

= 2.0.4 =
* Various minor bug fixes.
* Delete expired transients when clearing cache
* Tested up WordPress 6.4

= 2.0.3 =
* Various minor bug fixes.

= 2.0.2 =
* Various minor bug fixes.

= 2.0.1 =
* Various minor bug fixes.

= 2.0.0 =
* Complete rewrite of plugin with inspiration taken from Symfony core, following the principles of the SOLID pattern.
* Added the database update/migration system.
* Added the cache warming system.
* Added a new UI for the Settings page.
* Optimized events log system.
* Optimized WordPress events queue system.
* Optimized settings system.
* Optimized plugin bootstrap system.
* Tested PHP 5.6, 7.4 and 8.2.
* Tested WP version 6.3 and previous versions.
* Various minor bug fixes.

= 1.2.4 =
* Removed 'heartbeat' calls in the frontend and limited admin-side calls to every 120 seconds;
* Set tested up WP 6.2 version;

= 1.2.3 =
* Add x-aruba-cache header in site health

= 1.2.2 =
* Woocommerce save option bug fix.
* Page update content bug fix.
* Add no limit transient for json call.
* Various minor bug fixes and other improvements.

= 1.2.1 =
* Edit the Bootstrap method to fix the large menu editing problem.
* Action and filter loading system fixed.
* Various minor bug fixes and other improvements.

= 1.2.0 =
* Discriminant added to REST API requests to include FSE support and remove recurring clear cache requests.
* Clear function added to the 'post_updated hook' action to include support for the editor with meta-box added by plugins and themes.
* Log function added to files activated with WP_DEBUG set to true, created in plugin folder. N.B. If disabled, file will be deleted.
* FILTER_SANITIZE_STRING replaced by FILTER_SANITIZE_URL to ensure compatibility with PHP 8.1.
* ArubaHiSpeedCache_update_plugins_db() mode added to allow management of future database updates.
* Action and filter loading system within plugin fixed.
* Various bug fixes and other improvements.

= 1.1.2 =
* Problem regarding check resolved. In the event of a false positive during activation, go to the plugin page (../wp-admin/options-general.php?page=aruba-hispeed-cache) to repeat check.
* Various bug corrections and other improvements.

= 1.1.1 =
* Problem resolution.  Display "service can be activated" message when cache is purged.;
* Update Plugin URI value;
* Problem resolution. WP_CLI Activation and Deactivation;
* Additional option to view the check request information by visiting the page dominio.tld/wp-admin/options-general.php?page=aruba-hispeed-cache&debug=1

= 1.1.0 =
* Compatibility with PHP 5.6 and above;
* Compatibility with WordPress 5.4 and above;
* Monitoring of Aruba HiSpeed Cache service enabled/disabled;
* Message in the admin panel following the check: on the status of the service and if the website is hosted on an Aruba server or a server which is not compatible with the HiSpeed Cache service.

= 1.0.0 =
* First stable version released.

== Upgrade Notice ==

= 3.0.0 =
* Frontend revisioned.
* Add object cache managment.
* Various fixes.
* Tested up 6.8
