<?php
/**
 * A telmplate frame
 * php version 5.6
 *
 * @category Wordpress-plugin
 * @package  Aruba-HiSpeed-Cache
 * @author   Aruba Developer <hispeedcache.developer@aruba.it>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     none
 */

// phpcs:disable
/*if(isset($this->fields['sections']['general']['apc'])){
  unset($this->fields['sections']['general']['apc']);
}*/

//require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR .'sections' . DIRECTORY_SEPARATOR .'section-ahsc-service-status.php';
require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR .'sections' . DIRECTORY_SEPARATOR .'section-ahsc-enable-purge.php';
require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR .'sections' . DIRECTORY_SEPARATOR .'section-ahsc-cache-warmer.php';
require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR .'sections' . DIRECTORY_SEPARATOR .'section-ahsc-static-cache.php';
require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR .'sections' . DIRECTORY_SEPARATOR .'section-ahsc-apc.php';
