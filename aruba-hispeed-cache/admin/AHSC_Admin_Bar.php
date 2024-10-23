<?php
\add_action( 'admin_bar_menu', 'AHSC_add_admin_bar_menu_links' , 100 );

//die(var_export(__( 'Purge Cache','aruba-hispeed-cache' ),true));
const AHSC_MENUBAR_PARENT_ITEM = 'aruba_spa';
//$topurge = 'current-url';
//$AHSC_AB_title   =esc_html(__( 'Purge page cache', 'aruba-hispeed-cache' ));
	//var_dump(esc_html(__( 'Purge page cache', 'aruba-hispeed-cache' )));
/*
if ( \is_admin() ) {
	$topurge = 'all';
	$AHSC_AB_title   = esc_html(__( 'Purge Cache', 'aruba-hispeed-cache' )) ;
		//esc_html(__( 'Purge Cache', 'aruba-hispeed-cache' ));
}*/




/**
 * Netodo che aggiunge action link alla admin bar.
 *
 * @doc see https://developer.wordpress.org/reference/hooks/admin_bar_menu/
 *
 * @param  WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
 * @return void
 */
function AHSC_add_admin_bar_menu_links( $wp_admin_bar ) {
	//global $AHSC_AB_title,$topurge;
	$AHSC_AB_title   =esc_html(__( 'Purge page cache', 'aruba-hispeed-cache' ));
	//var_dump(esc_html(__( 'Purge page cache', 'aruba-hispeed-cache' )));

	if ( \is_admin() ) {
		$topurge = 'all';
		$AHSC_AB_title   = esc_html(__( 'Purge Cache', 'aruba-hispeed-cache' )) ;
		//esc_html(__( 'Purge Cache', 'aruba-hispeed-cache' ));
	}else{
		$topurge = 'current-url';
	}
    if ( is_user_logged_in() && current_user_can( 'manage_options' )){
    $wp_admin_bar->add_menu(
		array(
			'id'     => 'ahsc-purge-link',
			'parent' => ( ! \is_null( $wp_admin_bar->get_node( AHSC_MENUBAR_PARENT_ITEM ) ) ) ? AHSC_MENUBAR_PARENT_ITEM : false,
			'title'  => AHSC_Menu_get_title(),
			'meta'   => array(
				'title'        => $AHSC_AB_title,
				'data-topurge' => $topurge,
				'onclick'      => 'ahscBtnPurger(); return;'
			),
		)
	);

	    $wp_admin_bar->add_menu(
		    array(
			    'id'     => 'ahsc-transient-purge',
			    'parent' => ( ! \is_null( $wp_admin_bar->get_node( AHSC_MENUBAR_PARENT_ITEM ) ) ) ? AHSC_MENUBAR_PARENT_ITEM : false,
			    'title'  =>'<span class="ab-icon ahsc-ab-icon" aria-hidden="true"></span><span class="ab-label">'.esc_html(__( 'Purge Expired Transient', 'aruba-hispeed-cache' )).'</span>',
			    'meta'   => array(
				    'title'        => esc_html(__( 'Purge Expired Transient', 'aruba-hispeed-cache' )),
				    'onclick'      => 'ahscBtnTransientPurger(); return;'
			    ),
		    )
	    );

    }
}


/**
 * This method adds and localizes the toolbar.js on both the frontend and backend.
 *
 * @return void
 */
function AHSC_localize_toolbar_js() {

	if ( \is_admin() ) {
		$topurge = 'all';
	}else{
		$topurge = 'current-url';
	}
    if ( is_user_logged_in() && current_user_can( 'manage_options' )) {
        //global $topurge;
        $js_param = array(
            'ahsc_ajax_url' => \admin_url('admin-ajax.php'),
            'ahsc_topurge' => $topurge,
            'ahsc_nonce' => \wp_create_nonce('ahsc-purge-cache'),
        );
        \wp_add_inline_script('ahcs-toolbar', 'const AHSC_TOOLBAR = ' . \wp_json_encode($js_param), 'before');
    }
}

/**
 * Get the title whit icon.
 *
 * @return string
 */
function AHSC_Menu_get_title() {
	//global $AHSC_AB_title;
	$AHSC_AB_title   =esc_html(__( 'Purge page cache', 'aruba-hispeed-cache' ));

	if ( \is_admin() ) {
		$AHSC_AB_title   = esc_html(__( 'Purge Cache', 'aruba-hispeed-cache' )) ;
	}
	$title = '<span class="ab-icon ahsc-ab-icon" aria-hidden="true"></span><span class="ab-label">' . $AHSC_AB_title . '</span>';
	return $title;
}