<table class="form-table ahsc-table-ahsc_dboptimization ahsc_dboptimization">
	<tbody>
	<tr class="ahsc_dboptimization">
		<td style="position:relative;overflow:hidden;">
			<div class="ahsc_dboptimization_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
				<div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
					<span class="loader" style="position: relative;"></span>
				</div>
			</div>
			<div class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'Database table optimization', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></h1>
			</div>
			<legend style="display:inline-block">
				<?php
				echo wp_kses( __( 'This process improves data retrieval speed, making your site faster and more responsive for both visitors and you in the admin area. Enter the optimization date and it can be relaunched.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )
				?>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo wp_kses( __( 'This process improves data retrieval speed, making your site faster and more responsive for both visitors and you in the admin area. Enter the optimization date and it can be relaunched.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>

				<label style="display: inline-block;position:relative;cursor: default !important;" >
                    <span class="status-label" style="float:left;height: 34px;  margin-top: 4px;padding-top: 2px;">
                        <?php echo  wp_kses( __( ' Status:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ); ?>
                    </span>
                    <?php
                    $ahsc_current_dbopt_status=(AHSC_DBOPT_Check())?true: false;;

                    ?>
					<label class="switch" style="float:left;height: 34px;width:50%!important;cursor: default !important;">

                        <span id="ahsc-db-status-indicator" class="dot <?php echo   ($ahsc_current_dbopt_status)?"green": "yellow" ?>"></span>
                        <span id="ahsc-db-status-label" class="status-label" style="display: inline-block;height: 34px;vertical-align: middle;">
                            <?php
                            if(AHSC_DBOPT_Check()){
	                            //"checked"
	                            echo  wp_kses( __( 'Optimized', 'aruba-hispeed-cache' ), array( 'strong' => array() ) );
                            }else{
	                            echo  wp_kses( __( 'Not Optimized', 'aruba-hispeed-cache' ), array( 'strong' => array() ) );
                            }
                            ?>
                        </span>
                    </label>
				</label>
                <div>
                    <a href="#" id="ahsc-db-optimize" class="button button-secondary <?php echo esc_html( ($ahsc_current_dbopt_status)?"disabled":"");?>"   > <?php echo  wp_kses( __( 'Optimize', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ); ?> </a>

                    <a id="ahsc-db-optimize-default" style="position: relative;font-size: 14px;font-weight: bold;height: 40px;display: inline-block;margin-top: 10px;margin-left: 20px;  <?php echo   ($ahsc_current_dbopt_status)?"": "display:none;" ?>" href="#"> <?php echo  wp_kses( __( 'Restore default', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ); ?> </a>

                </div>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>