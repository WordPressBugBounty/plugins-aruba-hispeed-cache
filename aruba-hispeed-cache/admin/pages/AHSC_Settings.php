<?php
include "AHSC_Page.php";
class AHSC_Settings extends \AHSC\Pages\AHSC_Page {

    public $fields;

	protected function draw(){
		global $pagenow;

        if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die(
				esc_html__( 'Sorry, you need to be an administrator to use HiSpeed Cache.', 'aruba-hispeed-cache' )
			);
		}
		if(isset( $_POST['ahsc_reset_save'] )){
		  ahsc_reset_options();
		}else{
		  ahsc_save_options();
		}

		$this->add_fields();

		include_once AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR .'pages'.DIRECTORY_SEPARATOR .'views'.DIRECTORY_SEPARATOR .  'admin-settings.php';

	}

	/**
	 * This method add files to settings form.
	 *
	 * @return void
	 */
	private function add_fields() {
		$this->fields = array();

		$site_option=get_site_option( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME'] );

		$option       = ($site_option)?$site_option: AHSC_OPTIONS_LIST;

		$this->fields['sections']['general']['general'] = array(
			'ids'   => array( 'ahsc_enable_purge' ),
			'name'  =>wp_kses( __( 'Cache purging options', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'class' => '',
		);

		$this->fields['ahsc_enable_purge'] = array(
			'name'    => wp_kses( __( 'Enable automatic purge of the cache', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
				//\esc_html__( 'Enable automatic purge of the cache', 'aruba-hispeed-cache' ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_enable_purge',
			'checked' => \checked( $option['ahsc_enable_purge'] ?? 0 , true, false ),
		);

		$is_hidden = ! $option['ahsc_enable_purge' ];

		$this->fields['sections']['general']['settings_tittle'] = array(
			'title' =>  wp_kses( __( 'Automatically purge the entire cache when:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'type'  => 'title',
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);

		$this->fields['sections']['general']['homepage'] = array(
			'ids'   => array( 'ahsc_purge_homepage_on_edit', 'ahsc_purge_homepage_on_del' ),
			'name'  =>wp_kses( __( 'Home page:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);

		$this->fields['ahsc_purge_homepage_on_edit'] = array(
			'name'    => \wp_kses( __( 'A post (or page/custom post) is modified or added.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_purge_homepage_on_edit',
			'checked' => \checked( $option['ahsc_purge_homepage_on_edit'] ?? 0 , 1, false ),
		);

		$this->fields['ahsc_purge_homepage_on_del'] = array(
			'name'    => wp_kses( __( 'a <strong>published post</strong> (or page/custom post) is <strong>cancelled</strong>.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_purge_homepage_on_del',
			'checked' => \checked( $option['ahsc_purge_homepage_on_del'] ?? 0 , 1, false ),
		);

		$this->fields['sections']['general']['pages'] = array(
			'ids'   => array( 'ahsc_purge_page_on_mod', 'ahsc_purge_page_on_new_comment', 'ahsc_purge_page_on_deleted_comment' ),
			'name'  => wp_kses( __( 'Post/page/custom post:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);

		$this->fields['ahsc_purge_page_on_mod'] = array(
			'name'    => wp_kses( __( 'A post is published', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_purge_page_on_mod',
			'checked' => \checked( $option[ 'ahsc_purge_page_on_mod' ] ?? 0 , 1, false ),
		);

		$this->fields['ahsc_purge_page_on_new_comment'] = array(
			'name'    => wp_kses( __( 'A comment is published', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_purge_page_on_new_comment',
			'checked' => \checked( $option['ahsc_purge_page_on_new_comment' ] ?? 0 , 1, false ),
		);

		$this->fields['ahsc_purge_page_on_deleted_comment'] = array(
			'name'    => wp_kses( __( 'A comment is not approved or is deleted', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_purge_page_on_deleted_comment',
			'checked' => \checked( $option[ 'ahsc_purge_page_on_deleted_comment' ] ?? 0 , 1, false ),
		);

		$this->fields['sections']['general']['archives'] = array(
			'ids'    => array( 'ahsc_purge_archive_on_edit', 'ahsc_purge_archive_on_del' ),
			'name'   => wp_kses( __( 'Archives:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
				//\esc_html__( 'Archives:', 'aruba-hispeed-cache' ),
			'legend' => wp_kses( __( '(date, category, tag, author, custom taxonomies)', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
				//\esc_html__( '(date, category, tag, author, custom taxonomies)', 'aruba-hispeed-cache' ),
			'class'  => ( $is_hidden ) ? 'hidden' : '',
		);

		$this->fields['ahsc_purge_archive_on_edit'] = array(
			'name'    => wp_kses( __( 'a <strong>post</strong> (or page/custom post) is <strong>modified</strong> or <strong>added</strong>.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_purge_archive_on_edit',
			'checked' => \checked( $option['ahsc_purge_archive_on_edit' ] ?? 0 , 1, false ),
		);

		$this->fields['ahsc_purge_archive_on_del'] = array(
			'name'    => wp_kses( __( 'A published post (or page/custom post) is deleted', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_purge_archive_on_del',
			'checked' => \checked( $option['ahsc_purge_archive_on_del' ] ?? 0 , 1, false ),
		);

		$this->fields['sections']['general']['comments'] = array(
			'ids'   => array( 'ahsc_purge_archive_on_new_comment', 'ahsc_purge_archive_on_deleted_comment' ),
			'name'  => wp_kses( __( 'Comments', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
				//\esc_html__( 'Comments', 'aruba-hispeed-cache' ),
			// 'legend' => \esc_html__( '(date, category, tag, author, custom taxonomies)', 'aruba-hispeed-cache' ),
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);

		$this->fields['ahsc_purge_archive_on_new_comment'] = array(
			'name'    => wp_kses( __( 'A comment is published', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_purge_archive_on_new_comment',
			'checked' => \checked( $option[ 'ahsc_purge_archive_on_new_comment' ], 1, false ),
		);

		$this->fields['ahsc_purge_archive_on_deleted_comment'] = array(
			'name'    => wp_kses( __( 'A comment is not approved or is deleted', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_purge_archive_on_deleted_comment',
			'checked' => \checked( $option[ 'ahsc_purge_archive_on_deleted_comment' ] ?? 0 , 1, false ),
		);


		$this->fields['sections']['cache_warmer']['settings_tittle'] = array(
			'title' => wp_kses( __( 'Cache Warming:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
				//\esc_html__( 'Cache Warming:', 'aruba-hispeed-cache' ),
			'type'  => 'title',
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);

		$this->fields['sections']['cache_warmer']['general'] = array(
			'ids'   => array( 'ahsc_cache_warmer' ),
			'name'  =>  wp_kses( __( 'Cache Warming options', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
				//\esc_html__( 'Cache Warming options', 'aruba-hispeed-cache' ),
			'class' => ( $is_hidden ) ? 'ahsc_cache_warmer hidden' : 'ahsc_cache_warmer',
		);

		$this->fields['ahsc_cache_warmer'] = array(
			'name'    => "<strong>".wp_kses( __( 'Enables Cache Warming.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )."</strong>",
			'legend' => wp_kses( __( 'Cache Warming is the process through which webpages are preloaded in the cache so they can be displayed quicker.<br> When the cache is emptied, the homepage data and the last ten posts of the site are automatically renewed to guarantee faster page loading', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_cache_warmer',
			'checked' => \checked( $option[ 'ahsc_cache_warmer' ]  ?? 0 , 1, false ),
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);


		$this->fields['sections']['general']['cache_static']['settings_tittle'] = array(
			'title' => wp_kses( __( 'Static File Cache:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'type'  => 'title',
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['sections']['general']['cache_static'] = array(
			'ids'   => array( 'ahsc_static_cache' ),
			'name'  => wp_kses( __( 'Static File Cache options', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['ahsc_static_cache'] = array(
			'name'    => "<strong>".wp_kses( __( 'Optimize static file cache on browser.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )."</strong>",
			'legend' => wp_kses( __( 'Enable cache for static file on browser such image file, css file js file etc.etc.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_static_cache',
			'checked' => \checked( $option[ 'ahsc_static_cache' ] ?? 0 , 1, false ),
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);

		$this->fields['sections']['general']['apc']['settings_tittle'] = array(
			'title' => wp_kses( __( 'Apc', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'type'  => 'title',
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['sections']['general']['apc'] = array(
			'ids'   => array( 'ahsc_apc' ),
			'name'  => wp_kses( __( 'Object cache', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['ahsc_apc'] = array(
			'name'    => "<strong>".wp_kses( __( 'Enable object cache', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )."</strong>",
			'legend' => wp_kses( __( 'Reduces the number of queries made to the database and the related execution times for processing the queries necessary to display the pages, improving site loading performance via APCu.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_apc',
			'checked' => \checked( $option[ 'ahsc_apc' ] ?? 0 , 1, false ),
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);

		$this->fields['sections']['general']['html_optimizer']['settings_tittle'] = array(
			'title' => wp_kses( __( 'Optimize HTML code', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'type'  => 'title',
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['sections']['general']['html_optimizer'] = array(
			'ids'   => array( 'ahsc_html_optimizer' ),
			'name'  => wp_kses( __( 'Optimize HTML code', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['ahsc_html_optimizer'] = array(
			'name'    => "<strong>".wp_kses( __( 'Enable HTML code optimization', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )."</strong>",
			'legend' => wp_kses( __( 'Reduce the dimensions of the HTML page for faster loading times.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_html_optimizer',
			'checked' => \checked( $option[ 'ahsc_html_optimizer' ] ?? 0 , 1, false ),
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);

		$this->fields['sections']['general']['lazy_load']['settings_tittle'] = array(
			'title' => wp_kses( __( 'Optimize image loading', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'type'  => 'title',
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['sections']['general']['lazy_load'] = array(
			'ids'   => array( 'ahsc_lazy_load' ),
			'name'  => wp_kses( __( 'Optimize image loading', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['ahsc_lazy_load'] = array(
			'name'    => "<strong>".wp_kses( __( 'Enable optimization of image loading', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )."</strong>",
			'legend' => wp_kses( __( 'Improve page loading times using Lazy Load (asynchronous loading) of images.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_lazy_load',
			'checked' => \checked( $option[ 'ahsc_lazy_load' ] ?? 0 , 1, false ),
			'class' => ( $is_hidden ) ? 'hidden' : '',
		);



		$this->fields['sections']['general']['dns_preconnect']['settings_tittle'] = array(
			'title' => wp_kses( __( 'DNS Prefetch and Preconnect:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'type'  => 'title',
			'class' =>( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['sections']['general']['dns_preconnect'] = array(
			'ids'   => array( 'ahsc_dns_preconnect' ,'ahsc_dns_preconnect_domains'),
			'name'  => wp_kses( __( 'DNS Prefetch and Preconnect for external domain resources', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'class' =>( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['ahsc_dns_preconnect'] = array(
			'name'    => "<strong>".wp_kses( __( 'DNS Prefetch and Preconnect for external domain resources', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )."</strong>",
			'legend' => wp_kses( __( 'DNS Prefetch and Preconnect are used to reduce the time to establish a connection to external resources, like CSS, fonts, js from some third-party domain.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_dns_preconnect',
			'checked' => \checked( $option['ahsc_dns_preconnect'] ?? 0 , 1, false ),
			'class' =>( $is_hidden ) ? 'hidden' : '',
		);

		$text_is_hidden = ! $option['ahsc_dns_preconnect' ];
		$this->fields['ahsc_dns_preconnect_domains'] = array(
			'name'    => wp_kses( "<strong>".__( 'DNS Prefetch and Preconnect domain list.', 'aruba-hispeed-cache' )."</strong>", array( 'strong' => array() ) ),
			'legend' => wp_kses( __( 'Insert one external domain per line, for example "https://dominioesterno.it"', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) ),
			'type'    => 'textarea',
			'id'      => 'ahsc_dns_preconnect_domains',
			'class' =>( $text_is_hidden ) ? 'hidden' : '',
			'value'=>$option['ahsc_dns_preconnect_domains'] ?? ""
		);



		$this->fields['sections']['general']['xmlrpc_status']['settings_tittle'] = array(
			'title' => wp_kses( __( 'XML-RPC Status:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'type'  => 'title',
			'class' =>( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['sections']['general']['xmlrpc_status'] = array(
			'ids'   => array( 'ahsc_xmlrpc_status' ),
			'name'  => wp_kses( __( 'XML-RPC Status:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'class' =>( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['ahsc_xmlrpc_status'] = array(
			'name'    => "<strong>".wp_kses( __( 'XML-RPC', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )."</strong>",
			'legend' => wp_kses( __( 'Enhance the protection of your website from cyber attacks by disabling the XML-RPC function that allows data transfer.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_xmlrpc_status',
			'checked' => \checked( $option['ahsc_xmlrpc_status'] ?? 0 , 1, false ),
			'class' =>( $is_hidden ) ? 'hidden' : '',
		);


		$this->fields['sections']['general']['cron_status']['settings_tittle'] = array(
			'title' => wp_kses( __( 'WP-Cron Status:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'type'  => 'title',
			'class' =>( $is_hidden ) ? 'hidden' : '',
		);
		$this->fields['sections']['general']['cron_status'] = array(
			'ids'   => array( 'ahsc_cron_status','ahsc_cron_time' ),
			'name'  => wp_kses( __( 'WP-Cron status', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ,
			'class' =>( $is_hidden ) ? 'hidden' : '',
		);

		$cron_val=(wp_validate_boolean($option['ahsc_cron_status'])===false )?true:false;
		$this->fields['ahsc_cron_status'] = array(
			'name'    => "<strong>".wp_kses( __( 'WP-Cron', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )."</strong>",
			'legend' => wp_kses( __( 'Indicates the status of wp cron.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) ),
			'type'    => 'checkbox',
			'id'      => 'ahsc_cron_status',
			'checked' => \checked(  $cron_val?? 0 , 1, false ),
			'class' =>( $is_hidden ) ? 'hidden' : '',
		);
;
		$this->fields['ahsc_cron_time'] = array(

			'name'    => "<strong>".wp_kses( __( 'WP-Cron Interval', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )."</strong>",
			'legend' => wp_kses( __( 'indicates the time elapsed between one execution and another.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) ),
			'type'    => 'select',
			'id'      => 'ahsc_cron_time',
			'options'=>array('5'=>'300','15'=>'900','60'=>'3600','120'=>'7200','180'=>'10800'),
			'class' =>( $is_hidden ) ? 'hidden' : '',

		);




	}


}