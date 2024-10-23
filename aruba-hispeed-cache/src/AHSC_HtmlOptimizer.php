<?php
if ( isset( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_html_optimizer'] ) &&
     AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_html_optimizer'] ) {
	add_action('wp_loaded', 'ahsc_output_buffer_start');
	add_action('shutdown', 'ahsc_output_buffer_end');
}
function ahsc_output_buffer_start() {
	ob_start("ahsc_output_callback");
}
function ahsc_output_buffer_end() {
	ob_get_clean();
}
function ahsc_output_callback($buffer) {
	if(!is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {

		$buffer = preg_replace( '@\/\*(.*?)\*\/@s', ' ', $buffer); //remove  comment
		$buffer = preg_replace( '@((^|\t|\s|\r)\/{2,}.+?(\n|$))@s', ' ', $buffer); //remove  comment
		$buffer = preg_replace("/\s+|\n+|\r/", ' ', $buffer); // remove space and return
	}
	return $buffer;
}