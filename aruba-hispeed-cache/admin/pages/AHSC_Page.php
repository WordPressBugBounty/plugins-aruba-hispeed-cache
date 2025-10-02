<?php
namespace AHSC\Pages;
abstract class AHSC_Page {
	abstract protected function draw();

	private function loadStyles() {
		wp_register_style(
			'aruba-hispeed-cache-style', // handle name
			plugins_url( 'assets/css/option-page.css', dirname( __FILE__ ) ),
			[],
			AHSC_get_version()
		);

		wp_enqueue_style( 'aruba-hispeed-cache-style' );
		wp_enqueue_style('latofont','https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap');
	}

	private function loadJavascript() {

		wp_register_script(
			'aruba-hispeed-cache-script', // handle name
			plugins_url( 'assets/js/option-page.js', dirname( __FILE__ ) ),
			[],
			AHSC_get_version(),
			array(
				'strategy'  => 'defer',
				'in_footer'=> true
			)
		);

		wp_enqueue_script( 'aruba-hispeed-cache-script' );
		wp_localize_script( 'aruba-hispeed-cache-script', 'AHSC_OPTIONS_CONFIGS',
			array(
				'ahsc_ajax_url' => \admin_url( 'admin-ajax.php' ),
				'ahsc_topurge'  => 'all',
				'ahsc_nonce'    => \wp_create_nonce( 'ahsc-purge-cache' ),
				'ahsc_confirm'  => __( 'You are about to purge the entire cache. Do you want to continue?', 'aruba-hispeed-cache' ),
				'ahsc_reset_confirm'  => __( 'By confirming this action, all the plugin\'s original settings will be restored. Are you sure you want to proceed?', 'aruba-hispeed-cache' ),
				'ahsc_db_opt_status_active'=>__('Optimized', 'aruba-hispeed-cache' ),
				'ahsc_db_opt_status_disactive'=>__('Not optimized', 'aruba-hispeed-cache' )
			)
		);
	}

	public function buildPage() {
		$this->loadJavascript();
		$this->loadStyles();


		$this->draw();

	}
}
