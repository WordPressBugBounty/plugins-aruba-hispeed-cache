<?php
include "AHSC_Page.php";
class AHSC_Settings extends \AHSC\Pages\AHSC_Page {

    public $fields,$option;

	protected function draw(){
		global $pagenow;

        if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die(
				esc_html__( 'Sorry, you need to be an administrator to use HiSpeed Cache.', 'aruba-hispeed-cache' )
			);
		}
		/*if(isset( $_POST['ahsc_reset_save'] )){
		  ahsc_reset_options();
		}else{
		  ahsc_save_options();
		}*/

		$this->add_fields();

		include_once AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR .'pages'.DIRECTORY_SEPARATOR .'views'.DIRECTORY_SEPARATOR .  'admin-settings-new.php';

	}

	/**
	 * This method add files to settings form.
	 *
	 * @return void
	 */
	private function add_fields() {
		$this->fields = array();

		$site_option=get_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'] );

		$this->option       = ($site_option)?$site_option: AHSC_OPTIONS_LIST;

	}


}