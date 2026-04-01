<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_html_optimizer'] ) &&
     AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_html_optimizer'] ) {

	  add_action('template_redirect', 'ahsc_output_buffer_start');

}

function ahsc_output_buffer_start() {
	if(!is_admin()) {
		if(!is_customize_preview()) {
			ob_start( "ahsc_minify_html_fast" );
		}
	}

}

function ahsc_minify_html_fast($html) {
	$html = (string) $html;

	$html = trim(preg_replace_callback(
		'#<(script|style|pre|textarea)\b[^>]*>.*?</\1>(*SKIP)(*F)|\s{2,}|>\s+|\s+<#is',
		fn($m) => preg_match('/^>\s+$/', $m[0]) ? '>' :
			(preg_match('/^\s+</', $m[0]) ? '<' : ' '),
		$html
	));

	return trim($html);
}