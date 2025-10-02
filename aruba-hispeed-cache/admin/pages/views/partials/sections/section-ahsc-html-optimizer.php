<table class="form-table ahsc-table-ahsc_html_optimizer ahsc_html_optimizer">
	<tbody>
	<tr class="ahsc_html_optimizer">
        <td style="position:relative;overflow:hidden;">
            <div class="ahsc_html_optimizer_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                    <span class="loader" style="position: relative;"></span>
                </div>
            </div>
			<div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo  wp_kses( __( 'Optimize HTML code', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></h1>
                <!--span class="ahsc_html_optimizer_loader loader" style="float: right;top: -30px;right: 15px;"></span-->
			</div>
			<legend style="display:inline-block">
				<span><?php echo wp_kses( __( 'Reduce the dimensions of the HTML page for faster loading times.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo wp_kses( __( 'Reduce the dimensions of the HTML page for faster loading times.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>


				<label for="ahsc_html_optimizer" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo wp_kses( __( 'Enable HTML code optimization', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
					<?php
					$this->option[ 'ahsc_html_optimizer' ]= (isset($this->option[ 'ahsc_html_optimizer' ]))?$this->option[ 'ahsc_html_optimizer' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_html_optimizer']['default'];
					?>
					<label class="switch" style="float:right">
						<input
							type="checkbox"
							value="1"
							name="ahsc_html_optimizer"
							id="ahsc_html_optimizer"
							<?php echo esc_html( ($this->option[ 'ahsc_html_optimizer' ])?"checked":""); ?>
						/>
						<span class="slider round"></span>
					</label>
				</label>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>