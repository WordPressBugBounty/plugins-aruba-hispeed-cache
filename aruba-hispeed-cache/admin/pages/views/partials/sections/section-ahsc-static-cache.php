<table class="form-table ahsc-table-ahsc_static_cache ahsc_static_cache">
	<tbody>
	<tr class="ahsc_static_cache">
        <td style="position:relative;overflow:hidden;">
            <div class="ahsc_static_cache_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                    <span class="loader" style="position: relative;"></span>
                </div>
            </div>
			<div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'Optimize the cache of static files on the browser', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></h1>
                <!--span class="ahsc_static_cache_loader loader" style="float: right;top: -30px;right: 15px;"></span-->
			</div>
            <legend style="display:inline-block">
				<?php
				echo wp_kses( __( 'Manage the expiry of static files (JPEG, GIF, PNG, WebPL, SVG, X-Icon, CSS, JavaScript) and set the length of time you want to save them in the local cache on the browser, before they are deleted.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
				?>
            </legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo wp_kses( __( 'Manage the expiry of static files (JPEG, GIF, PNG, WebPL, SVG, X-Icon, CSS, JavaScript) and set the length of time you want to save them in the local cache on the browser, before they are deleted.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></span>
				</legend>

				<label for="ahsc_static_cache" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo  wp_kses( __( 'Optimize the cache on browser', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
					<?php
					$this->option[ 'ahsc_static_cache' ]= (isset($this->option[ 'ahsc_static_cache' ]))?$this->option[ 'ahsc_static_cache' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_static_cache']['default'];
					?>
					<label class="switch" style="float:right">
						<input
							type="checkbox"
							value="1"
							name="ahsc_static_cache"
							id="ahsc_static_cache"
							<?php echo esc_html( ($this->option[ 'ahsc_static_cache' ])?"checked":""); ?>
						/>

						<span class="slider round"></span>
					</label>
				</label>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>