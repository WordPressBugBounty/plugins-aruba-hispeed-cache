<table class="form-table ahsc-table-ahsc_debug_status ahsc_debug_status">
	<tbody>
	<tr class="ahsc_debug_status">
        <td style="position:relative;overflow:hidden;">
            <div class="ahsc_debug_status_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                    <span class="loader" style="position: relative;"></span>
                </div>
            </div>
			<div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'WP Debug Status:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )  ?></h1>
                <!--span class="ahsc_debug_status_loader loader" style="float: right;top: -30px;right: 15px;"></span-->
			</div>
			<legend style="display:inline-block">
				<span><?php echo  wp_kses( __( 'Control and manage debug status.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo  wp_kses( __( 'Control and manage debug status.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>

				<label for="ahsc_debug_status" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo wp_kses( __( 'Enable WP Debug', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
					<?php
					$debug_status=false;
                    $wpc_transformer = new HASC_WPCT(  ABSPATH . 'wp-config.php' );
					if ( $wpc_transformer->exists( 'constant', 'WP_DEBUG' ) ) {
						$debug_status=true;
					}
                    ?>
					<label class="switch" style="float:right">
						<input
							type="checkbox"
							value="1"
							name="ahsc_debug_status"
							id="ahsc_debug_status"
							<?php echo esc_html( ($debug_status)?"checked":""); ?>
						/>
						<span class="slider round"></span>
					</label>
				</label>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>