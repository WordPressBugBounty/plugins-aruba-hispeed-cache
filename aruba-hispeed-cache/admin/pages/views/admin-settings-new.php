<div class="wrap ahsc-wrapper">
	<div id="ahsc-main">

		<div class="ahsc-settings-header">
			<h1 class="ahsc-settings-title" style="font-weight: 600!important;color:gray;!important;width:52%;margin:0 auto;">
				<img height="100px" width="100px" style="vertical-align: middle" src="<?php echo esc_html(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEURL']). 'admin' . esc_html(DIRECTORY_SEPARATOR) ?>/assets/img/icon-256x256.png"/>
				<?php \esc_html_e( 'Aruba HiSpeed Cache', 'aruba-hispeed-cache' ); ?>
                <span style=" background: #eee;color: #50565d8a;padding: 5px;font-size: 12px;vertical-align: middle;"><?php
                    $plugin_data = get_plugin_data( WP_PLUGIN_DIR."/aruba-hispeed-cache/aruba-hispeed-cache.php");
	                $plugin_version = $plugin_data['Version'];
                    echo $plugin_version;?></span>
			</h1>
            <div style="position:relative;display:block;margin:0px auto; width:52%; padding:20px 0 20px 0;">
                <?php echo wp_kses( __( 'Aruba HiSpeed Cache is the plugin that allows you to increase the page loading speed, optimizing cache management and improving the performance of your site, in order to offer a better browsing experience for your users.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )  ?>
            </div>
            <div class="ahsc-actions-wrapper">
                <table class="button-table ahst-table" style="text-align: inherit!important">
                    <tr>
                        <td>
							<?php /*
							\submit_button( __( 'Save changes', 'aruba-hispeed-cache' ), 'primary', 'ahsc_settings_save', false, array( 'form' => 'ahsc-settings-form' ) );
							*/?>
                            <a id="purgeall" href="#" class="button button-secondary"> <?php  echo \esc_html( __('Purge entire cache', 'aruba-hispeed-cache') ); ?></a>

                            <?php
							\submit_button( __( 'Reset default value', 'aruba-hispeed-cache' ), 'primary', 'ahsc_reset_save', false, array( 'form' => 'ahsc-settings-form' ) );
							?>
                            </td>
                    </tr>
                </table>
            </div>

			<h2 class="nav-tab-wrapper ahsc-settings-nav">
				<a class="nav-tab nav-tab-active" data-tab="#general"><?php esc_html_e( 'Cache', 'aruba-hispeed-cache' ); ?></a>
                <a class="nav-tab " data-tab="#performance"><?php esc_html_e( 'Performance', 'aruba-hispeed-cache' ); ?></a>
				<?php if (WP_DEBUG  && is_dir(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH']."/Debug")) : ?>
					<a class="nav-tab" data-tab="#debug"><?php esc_html_e( 'Debug', 'aruba-hispeed-cache' ); ?></a>
				<?php
				//var_dump(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc'])  ;

				if(isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']) && isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc']!==false ){ ?>


                <a class="nav-tab " data-tab="#apc"><?php esc_html_e( 'APCu', 'aruba-hispeed-cache' ); ?></a>
<?php } ?>
				<?php endif; ?>
			</h2>
		</div>
		<div id="general" class="ahsc-tab ahsc-options-wrapper">
            <div style="padding-top:30px;padding-bottom:30px;">
		     <?php
				require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR .'pages'.DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR  . 'partials' . DIRECTORY_SEPARATOR . 'admin-tab-general-new.php'; ?>

            </div>
		</div>
        <div id="performance" class="ahsc-tab hidden ahsc-options-wrapper">
            <div style="padding-top:30px; padding-bottom:30px;">
           <?php
			require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR .'pages'.DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR  . 'partials' . DIRECTORY_SEPARATOR . 'admin-tab-performance.php'; ?>
            </div>
        </div>

		<?php if ( WP_DEBUG && is_dir(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH']."/Debug") ) : ?>

			<div id="debug" class="ahsc-tab hidden">
                <div style="padding-top:30px;padding-bottom:30px;margin: 0 auto;width: 52%;">
				<?php require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH']. 'Debug'.DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR  . 'partials' . DIRECTORY_SEPARATOR . 'admin-tab-debug.php'; ?>
                </div>
            </div>

        <?php
       // var_dump(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc'])  ;

            if(isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']) && isset(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_apc']!==false ){ ?>

            <div id="apc" class="ahsc-tab hidden ahsc-options-wrapper">
                <div style="padding-top:30px; padding-bottom:30px;">
                   <?php
					require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'Debug'.DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR  . 'partials' . DIRECTORY_SEPARATOR . 'admin-tab-apc.php'; ?>
                </div>
            </div>

        <?php } ?>

		<?php endif; ?>

	</div> <!-- End of #ahsc-main -->

	<div class="clear"></div>
</div> <!-- End of .wrap .ahsc-wrapper -->