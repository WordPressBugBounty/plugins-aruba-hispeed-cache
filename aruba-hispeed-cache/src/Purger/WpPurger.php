<?php //@phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * ArubaHiSpeedCacheWpPurger
 * php version 7.4
 *
 * @category Wordpress-plugin
 * @package  Aruba-HiSpeed-Cache
 * @author   Aruba Developer <hispeedcache.developer@aruba.it>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     \ArubaHiSpeedCache\Run_Aruba_Hispeed_cache()
 * @since    1.1.3
 */

namespace ArubaSPA\HiSpeedCache\Purger;

// Unqualified defined() on purpose: inside a namespace PHP still resolves it to the
// global function, and Plugin Check only recognises this exact guard shape.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! \class_exists( __NAMESPACE__ . 'WpPurger' ) ) {
	// phpcs:disable WordPress.NamingConventions
	/**
	 * Undocumented class
	 */
	class WpPurger extends AbstractPurger {

		/**
		 * Purge the cache of a single page
		 *
		 * @param  string $url The url to purge.
		 * @return void
		 */
		public function purgeUrl( $url ) {
			// $site_url = $this->getParseSiteUrl();
			// $host = $site_url['host'];

			$_url = \filter_var( $url, FILTER_SANITIZE_URL );

			// Logger.
			if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
				// Logger.
				AHSC_log(  'targhet ' . $_url, 'purgeUrl()'  );
				// Logger.
			}
			// Logger.

			$this->doRemoteGet( $_url );

			\delete_expired_transients(true);
			$this->flush_wp_object_cache();
		}

		/**
		 * Purge the cache of a list of pages
		 *
		 * @param  array $urls The urls to purge.
		 * @return void
		 */
		public function purgeUrls( $urls ) {
			foreach ( $urls as $url ) {
				$this->doRemoteGet( $url );

				// Logger.
				if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
					// Logger.
					AHSC_log(  'targhet ' . $url, 'purgeUrl()' );
					// Logger.
				}
				// Logger.
			}


			\delete_expired_transients(true);
			$this->flush_wp_object_cache();
		}

		/**
		 * Purge the alla chace of site
		 *
		 * @return void
		 */
		public function purgeAll() {
			// Logger.
			global $logger;

			if(class_exists('\ArubaSPA\HiSpeedCache\Debug\Logger')) {
				// Logger.
				AHSC_log( 'targhet /', 'purgeAll()' );
				// Logger.
			}

			$this->doRemoteGet( '/' );

			\delete_expired_transients(true);
			$this->flush_wp_object_cache();
		}

		/**
		 * DoRemoteGet
		 *
		 * @param string $target path to purge.
		 *
		 * @return void
		 */
		public function doRemoteGet( $target = '/' ) {
			$purgeUrl = $this->preparePurgeRequestUri( $target );

			$blog_id = null;

			if ( is_multisite() ) {
				$blog_id = \get_current_blog_id();
			}

			$host = \wp_parse_url( \get_site_url( $blog_id ) );
			$host = $host['host'];

			// Logger.
			global $logger;

			if(class_exists('\ArubaSPA\HiSpeedCache\Debug\Logger')) {
				// Logger.
				AHSC_log( $purgeUrl, '_targhet' );
				// Logger.
			}
			// Logger.

			\wp_remote_get(
				$purgeUrl,
				array(
					'timeout' => $this->timeOut,
					'headers' => array(
						'Host' => $host,
					),
				)
			);
		}

		/**
		 * Whap form wp_cache_flush.
		 *
		 * @see https://developer.wordpress.org/reference/functions/wp_cache_flush/.
		 *
		 * @return bool
		 */
		public function flush_wp_object_cache() {
			$wp_object_cache = \wp_cache_flush();
			if ( $wp_object_cache ) {
				// Logger.
				global $logger;

				if(class_exists('\ArubaSPA\HiSpeedCache\Debug\Logger') ) {
					// Logger.
					AHSC_log( 'flush wp_object_cache with success....' ,'flush_wp_object_cache');
					// Logger.
				}
				// Logger.
			}
			return $wp_object_cache;
		}

	}
	// phpcs:enable
}
