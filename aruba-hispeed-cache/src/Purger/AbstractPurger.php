<?php //@phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * ArubaHiSpeedCacheWpPurger
 * php version 5.6
 *
 * @category Wordpress-plugin
 * @package  Aruba-HiSpeed-Cache
 * @author   Aruba Developer <hispeedcache.developer@aruba.it>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     \ArubaHiSpeedCache\Run_Aruba_Hispeed_cache()
 * @since    1.1.3
 */

namespace ArubaSPA\HiSpeedCache\Purger;

if ( ! \class_exists( __NAMESPACE__ . 'AbstractPurger' ) ) {
	// phpcs:disable WordPress.NamingConventions
	/**
	 * AbstracPurger.
	 */
	abstract class AbstractPurger {
		/**
		 * $servr_host for the requst
		 *
		 * @var string
		 */
		protected $serverHost;

		/**
		 * $server_port for the request
		 *
		 * @var string
		 */
		protected $serverPort;

		/**
		 * $time_out of request
		 *
		 * @var integer
		 */
		protected $timeOut;

		/**
		 * Purge the cache of a single page
		 *
		 * @param  string $url The url to purge.
		 * @return void
		 */
		abstract public function purgeUrl( $url );

		/**
		 * Purge the cache of a list of pages
		 *
		 * @param  array $urls The urls to purge.
		 * @return void
		 */
		abstract public function purgeUrls( $urls );

		/**
		 * Purge the alla chace of site
		 *
		 * @return void
		 */
		abstract public function purgeAll();

		/**
		 * DoRemoteGet
		 *
		 * @param string $target path to purge.
		 *
		 * @return void
		 */
		abstract public function doRemoteGet( $target = '/' );

		/**
		 * PreparePurgeRequestUri
		 *
		 * @param string $url Url to prepare.
		 *
		 * @return string for the purge request.
		 */
		public function preparePurgeRequestUri( $url ) {
			return \sprintf(
				'http://%s:%s/purge%s',
				$this->getServerHost(),
				$this->getServerPort(),
				preg_replace( '|^(https?:)?//[^/]+(/?.*)|i', '$2', filter_var( $url, FILTER_SANITIZE_URL ) )
			);
		}

		/**
		 * Set the purger.
		 *
		 *  $config [
		 *  'time_out'     => int 5;
		 *  'server_host'  => string '127.0.0.1'
		 *  'server_port'  => string '8889'
		 *  ];
		 *
		 * @param  array $configs The confi for the purger.
		 * @return void
		 */
		public function setPurger( $configs ) {
			$this->setTimeOut( $configs['time_out'] );
			$this->setServerHost( $configs['server_host'] );
			$this->setServerPort( $configs['server_port'] );
		}

		/**
		 * Get undocumented variable
		 *
		 * @return integer
		 */
		public function getTimeOut() {
			return $this->timeOut;
		}

		/**
		 * Set undocumented variable
		 *
		 * @param integer $timeOut Undocumented variable.
		 *
		 * @return self
		 */
		public function setTimeOut( $timeOut ) {
			$this->timeOut = $timeOut;

			return $this;
		}

		/**
		 * Get undocumented variable
		 *
		 * @return string
		 */
		public function getServerPort() {
			return $this->serverPort;
		}

		/**
		 * Set undocumented variable
		 *
		 * @param string $serverPort Undocumented variable.
		 *
		 * @return self
		 */
		public function setServerPort( $serverPort ) {
			$this->serverPort = $serverPort;
			return $this;
		}

		/**
		 * Get undocumented variable
		 *
		 * @return string
		 */
		public function getServerHost() {
			return $this->serverHost;
		}

		/**
		 * Set undocumented variable
		 *
		 * @param string $serverHost Undocumented variable.
		 *
		 * @return self
		 */
		public function setServerHost( $serverHost ) {
			$this->serverHost = $serverHost;
			return $this;
		}
	}
	// phpcs:enable
}
