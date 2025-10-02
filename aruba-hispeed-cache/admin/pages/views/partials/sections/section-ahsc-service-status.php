<table class="form-table ahsc-table-ahsc_service_status ahsc_service_status">
	<tbody>
	<tr class="ahsc_service_status">
		<td>
			<div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'Cache Status ', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></h1>
			</div>
			<legend style="display:inline-block">
				<?php
				echo  wp_kses( __( 'check if the cache service is enabled and active in the domain', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )
				?>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo  wp_kses( __( 'check if the cache service is enabled and active in the domain', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>

				<label for="ahsc_service_status" style="display: inline-block;position:relative;" >
                    <?php
                    global $check_parameters;
                    ?>
                    <label class="<?php echo $check_parameters['esit'];?>">
                    <?php

                    $localize_link = AHSC_LOCALIZE_LINK; // For php 5.6 compatibility.
                    $notice = null;
                    $lng=strtolower(substr( get_bloginfo ( 'language' ), 0, 2 ));
                      switch($check_parameters['esit']){
	                      case AVAILABLE:
		                     echo  \sprintf(
			                      \wp_kses(
			                      // translators: %1$s: the pca url.
			                      // translators: %2$s: the guide url.
				                      __( '<strong>The HiSpeed Cache service is not enabled.</strong> To activate it, go to your domain <a href="%1$s" rel="nofollow" target="_blank">control panel</a> (verifying the status may take up to 15 minutes). For further details <a href="%2$s" rel="nofollow" target="_blank">see our guide</a>.', 'aruba-hispeed-cache' ),
				                      array(
					                      'strong' => array(),
					                      'a'      => array(
						                      'href'   => array(),
						                      'target' => array(),
						                      'rel'    => array(),
					                      ),
				                      )
			                      ),
			                      esc_html( $localize_link['link_aruba_pca'][$lng] ),
			                      esc_html( $localize_link['link_guide'][$lng] ));
		                      break;
	                      case UNAVAILABLE:
		                     echo  \sprintf(
			                      \wp_kses(
			                      // translators: %s: the assistance url.
				                      __( '<strong>The HiSpeed Cache service with which the plugin interfaces is not available on the server that hosts your website.</strong> To use HiSpeed Cache and the plugin, please contact <a href="%s" rel="nofollow" target="_blank">support</a>.', 'aruba-hispeed-cache' ),
				                      array(
					                      'strong' => array(),
					                      'a'      => array(
						                      'href'   => array(),
						                      'target' => array(),
						                      'rel'    => array(),
					                      ),
				                      )
			                      ),
			                      esc_html( $localize_link['link_assistance'][$lng] )
		                      );
		                      break;
	                      case NOARUBASERVER:
		                     echo \sprintf(
			                      \wp_kses(
			                      // translators: %s: the hosting truck url.
				                      __( '<strong>The Aruba HiSpeed Cache plugin cannot be used because your WordPress website is not hosted on an Aruba hosting platform.</strong> Buy an <a href="%s" rel="nofollow" target="_blank">Aruba Hosting service</a> and migrate your website to use the plugin.', 'aruba-hispeed-cache' ),
				                      array(
					                      'strong' => array(),
					                      'a'      => array(
						                      'href'   => array(),
						                      'target' => array(),
						                      'rel'    => array(),
					                      ),
				                      )
			                      ),
			                      esc_html( $localize_link[ 'link_hosting_truck' ][$lng] )
		                      );
                              break;
                              default:
	                              echo  \sprintf(
		                              \wp_kses(
		                              // translators: %1$s: the pca url.
		                              // translators: %2$s: the guide url.
			                              __( '<strong>The HiSpeed Cache service is enabled.</strong>  For further details <a href="%1$s" rel="nofollow" target="_blank">see our guide</a>.', 'aruba-hispeed-cache' ),
			                              array(
				                              'strong' => array(),
				                              'a'      => array(
					                              'href'   => array(),
					                              'target' => array(),
					                              'rel'    => array(),
				                              ),
			                              )
		                              ),
		                              esc_html( $localize_link['link_guide'][$lng] ));

                      }
                    ?>
                    </label>

				</label>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>