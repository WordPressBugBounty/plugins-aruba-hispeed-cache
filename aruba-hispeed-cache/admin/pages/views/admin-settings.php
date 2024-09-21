<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap ahsc-wrapper">
	<div id="ahsc-main">

		<div class="ahsc-settings-header">
			<h1 class="ahsc-settings-title">
				<img height="100px" width="100px" style="vertical-align: middle" src="<?php echo esc_html(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEURL']). 'admin' . esc_html(DIRECTORY_SEPARATOR) ?>/assets/img/icon-256x256.png"/>
				<?php \esc_html_e( 'Aruba HiSpeed Cache', 'aruba-hispeed-cache' ); ?>
			</h1>

			<h2 class="nav-tab-wrapper ahsc-settings-nav">
				<a class="nav-tab nav-tab-active" data-tab="#general"><?php \esc_html_e( 'General', 'aruba-hispeed-cache' ); ?></a>

				<?php if (WP_DEBUG ) : ?>
					<a class="nav-tab" data-tab="#debug"><?php \esc_html_e( 'Debug', 'aruba-hispeed-cache' ); ?></a>
				<?php endif; ?>
			</h2>
		</div>
		<div id="general" class="ahsc-tab ahsc-options-wrapper">
			<form id="ahsc-settings-form" method="post" action="#" name="ahsc-settings-form" class="clearfix" encoding="multipart/form-data">
				<input type="hidden" name="ahs-settings-nonce" value="<?php echo esc_attr( \wp_create_nonce( 'ahs-save-settings-nonce' ) ); ?>" />
				<?php
				require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR .'pages'.DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR  . 'partials' . DIRECTORY_SEPARATOR . 'admin-tab-general.php'; ?>
			</form>
            <div class="ahsc-actions-wrapper">
                <table class="button-table ahst-table">
                    <tr>
                        <th></th>
                        <td>
							<?php
							\submit_button( __( 'Save changes', 'aruba-hispeed-cache' ), 'primary', 'ahsc_settings_save', false, array( 'form' => 'ahsc-settings-form' ) );
							?>
                            <a id="purgeall" href="#" class="button button-secondary"> <?php  echo \esc_html( __('Purge entire cache', 'aruba-hispeed-cache') ); ?></a>
                        </td>
                    </tr>
                </table>
            </div>
		</div>

		<?php if ( WP_DEBUG ) : ?>
			<div id="debug" class="ahsc-tab hidden" style="padding:20px;">
				<?php require AHSC_CONSTANT['ARUBA_HISPEED_CACHE_BASEPATH'] . 'admin' . DIRECTORY_SEPARATOR . 'pages'.DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR  . 'partials' . DIRECTORY_SEPARATOR . 'admin-tab-debug.php'; ?>
			</div>
		<?php endif; ?>



	</div> <!-- End of #ahsc-main -->

	<div id="ahsc-side-bar">
	</div> <!-- End of #ahsc-main -->
	<div class="clear"></div>
</div> <!-- End of .wrap .ahsc-wrapper -->