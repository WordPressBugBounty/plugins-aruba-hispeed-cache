<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<table class="form-table ahsc-table-ahsc_debug_status ahsc_debug_status">
	<tbody>
	<tr class="ahsc_debug_status">
        <td style="position:relative;overflow:hidden;">
            <div class="ahsc_debug_status_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                    <span class="loader" style="position: relative;"></span>
                </div>
            </div>
			<!--div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'CDN', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )  ?></h1>
			</div-->
			<legend style="display:inline-block">
				<span><?php echo  wp_kses( __( 'The <strong>Content Delivery Network</strong> (CDN) reduces website loading times by distributing content across a network of remote servers. You can manage the CDN through the Aruba control panel.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo  wp_kses( __( 'The <strong>Content Delivery Network</strong> (CDN) reduces website loading times by distributing content across a network of remote servers. You can manage the CDN through the Aruba control panel.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>

				<label for="ahsc_cdn_status" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <strong>
                        <?php
                        echo wp_kses( __('Status:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                        </strong>
                    </span>
                            <?php
                            $ahsc_current_dbopt_status=false;
                            $ahsc_data=AHSC_check();

                            if(isset($ahsc_data['cdn_status']) && array_keys($ahsc_data,'cdn_status')){
	                            //presen
	                            $ahsc_value=$ahsc_data['cdn_status'];
                                //var_dump($value);
	                            if($ahsc_value){
		                            //attiva
		                            $ahsc_var= wp_kses( __( 'Enabled', 'aruba-hispeed-cache' ), array( 'strong' => array() ) );
		                            $ahsc_current_dbopt_status=true;
	                            }else{
		                            //disattivo
		                            $ahsc_var=wp_kses( __( 'Disabled', 'aruba-hispeed-cache' ), array( 'strong' => array() ) );
	                            }
                            }else{
	                            //disactive
	                            $ahsc_var=wp_kses( __( 'No active', 'aruba-hispeed-cache' ), array( 'strong' => array() ) );
                            }

                            ?>
                    <span id="ahsc-cdn-status-indicator" class="dot <?php echo   ($ahsc_current_dbopt_status)?"green": "yellow" ?>"></span>
                    <span id="ahsc-cdn-status-label" class="status-label" style="display: inline-block;height: 34px;vertical-align: middle;">
                        <?php echo esc_html($ahsc_var); ?>
                        </span>
				</label>
			</fieldset>
            <div>
                <a id="cdn-panel-admin" class="button button-secondary" href="https://admin.aruba.it/PannelloAdmin/Login.aspx" target="_blank" style="font-weight: bold;font-size: 14px;" ><?php echo wp_kses( __( 'ACCESS THE PANEL', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )  ?> </a>
            </div>
		</td>
	</tr>
	</tbody>
</table>