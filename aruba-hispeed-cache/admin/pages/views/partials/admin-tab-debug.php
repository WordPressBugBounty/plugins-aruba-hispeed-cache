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

?>

<h2 class="ahsc-title " ><?php esc_html_e( 'Purging Log Actions', 'aruba-hispeed-cache' ); ?></h2>

<?php $log_content = ahsc_get_debug_file_content(); ?>

<textarea cols="30" rows="15" style="width: 100%;" readonly>
<?php
if ( false !== $log_content ) {
	echo esc_html( $log_content );
} else {
	esc_html_e( 'File not found. The log file is not created, or is removed automatically, if the WordPress debugging feature is not enabled.', 'aruba-hispeed-cache' );
}
?>
</textarea>

<h2 class="ahsc-title "><?php esc_html_e( 'Service check request headers.', 'aruba-hispeed-cache' ); ?></h2>

<textarea cols="30" rows="15" style="width: 100%;" readonly>
<?php echo esc_html( debugger() ); ?>
</textarea>
