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
	 * Purges the Aruba HiSpeed Cache proxy.
	 *
	 * The three purge methods deliberately do **not** touch the WordPress object cache.
	 * They used to end with flush_wp_object_cache(), which on an APCu install means
	 * apcu_clear_cache() — the whole shared segment for the PHP pool, not just this site.
	 * Because every content event routes through here, editing one post wiped the object
	 * cache for everything sharing that pool, and each wipe left concurrent requests
	 * rebuilding alloptions and the post/term/meta caches from MySQL at the same time.
	 *
	 * Dropping it is safe because WordPress has already invalidated what changed by the
	 * time these run — clean_post_cache() inside wp_insert_post(), clean_term_cache()
	 * inside wp_update_term()/wp_delete_term(), clean_comment_cache() plus
	 * wp_update_comment_count() around the comment hooks, each of which also bumps the
	 * group's last_changed and so invalidates cached queries. Whether core's invalidation
	 * runs just before or just after the purge hook does not matter: it happens in the same
	 * request, and nothing reads the cache in between.
	 *
	 * Flushing the object cache is now an explicit decision at the call site — call
	 * flush_wp_object_cache() alongside the purge. Reserve it for an action the user asked
	 * for, or for an event core does not invalidate at all (a plugin being activated, a
	 * theme switch).
	 *
	 * For the same reason they no longer call delete_expired_transients( true ) either.
	 * That cleanup is still needed — with the drop-in installed, core's daily event and
	 * get_transient()'s delete-on-read both no-op — but it is housekeeping over rows that
	 * accrue across days, not work that belongs in a post save. It now runs once a day
	 * from ahsc_delete_expired_transients_forced() in src/AHSC_Functions.php, hooked to
	 * the delete_expired_transients event core already schedules.
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

			/*
			 * The response is discarded either way, so do not pay to parse it. Be precise
			 * about what this does buy, because the name oversells it: with the cURL
			 * transport 'blocking' => false only skips registering the header and body
			 * callbacks and skips building the response object — curl_exec() still runs to
			 * completion and still waits out the timeout. Only the fsockopen transport turns
			 * it into a genuine fire-and-forget (write, close, return). So a purge against a
			 * hung proxy still costs up to timeOut seconds per URL on a normal host.
			 *
			 * Shortening the timeout would not help much either: Requests clamps it with
			 * max($timeout, 1), so anything under a second silently becomes one. Two routes
			 * would actually bound it: curl_multi via Requests::request_multiple(), so the
			 * batch costs one round trip instead of N, or a raw socket write that never waits.
			 */
			\wp_remote_get(
				$purgeUrl,
				array(
					'timeout'  => $this->timeOut,
					'blocking' => false,
					'headers'  => array(
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
