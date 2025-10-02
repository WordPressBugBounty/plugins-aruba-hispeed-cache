<table class="form-table ahsc-table-ahsc_apc ahsc_apc">
	<tbody>
	<tr class="ahsc_apc">
		<td style="position:relative;overflow:hidden;">
            <div class="ahsc_apc_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                    <span class="loader" style="position: relative;"></span>
                </div>
            </div>
			<div class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'Object cache', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></h1>
				<!--span class="ahsc_apc_loader loader" style="float: right;top: -30px;right: 15px;"></span-->
			</div>
			<legend style="display:inline-block">
				<?php
				echo wp_kses( __( 'Reduces the number of queries made to the database and the related execution times for processing the queries necessary to display the pages, improving site loading performance via APCu.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )
				?>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo wp_kses( __( 'Reduces the number of queries made to the database and the related execution times for processing the queries necessary to display the pages, improving site loading performance via APCu.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>

				<label for="ahsc_apc" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo  wp_kses( __( 'Enable object cache', 'aruba-hispeed-cache' ), array( 'strong' => array() ) );
                        ?>
                    </span>
					<?php
					  $c_opt=get_site_option(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS_NAME']);
                      ?>
					<label class="switch" style="float:right">
						<input
							type="checkbox"
							value="1"
							name="ahsc_apc"
							id="ahsc_apc"
							<?php echo esc_html( ($c_opt['ahsc_apc'])?"checked":""); ?>
						/>

						<span class="slider round"></span>
					</label>
				</label>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>