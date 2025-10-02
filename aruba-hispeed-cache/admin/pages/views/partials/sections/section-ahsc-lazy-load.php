<table class="form-table ahsc-table-ahsc_lazy_load ahsc_lazy_load">
	<tbody>
	<tr class="ahsc_lazy_load">
        <td style="position:relative;overflow:hidden;">
            <div class="ahsc_lazy_load_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                    <span class="loader" style="position: relative;"></span>
                </div>
            </div>
			<div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'Optimize image loading', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></h1>
                <!--span class="ahsc_lazy_load_loader loader" style="float: right;top: -30px;right: 15px;"></span-->
			</div>
			<legend style="display:inline-block">
                <span><?php echo wp_kses( __( 'Improve page loading times using Lazy Load (asynchronous loading) of images.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
            </legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo wp_kses( __( 'Improve page loading times using Lazy Load (asynchronous loading) of images.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>


				<label for="ahsc_lazy_load" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo  wp_kses( __( 'Enable optimization of image loading', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array()))
                        ?>
                    </span>
					<?php
					$this->option[ 'ahsc_lazy_load' ]= (isset($this->option[ 'ahsc_lazy_load' ]))?$this->option[ 'ahsc_lazy_load' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_lazy_load']['default'];
					?>
					<label class="switch" style="float:right">
						<input
							type="checkbox"
							value="1"
							name="ahsc_lazy_load"
							id="ahsc_lazy_load"
							<?php echo esc_html( ($this->option[ 'ahsc_lazy_load' ])?"checked":""); ?>
						/>
						<span class="slider round"></span>
					</label>
				</label>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>