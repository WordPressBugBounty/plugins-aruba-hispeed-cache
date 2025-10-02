<table class="form-table ahsc-table-ahsc_cache_warmer ahsc_cache_warmer">
	<tbody>
	<tr class="ahsc_cache_warmer">
        <td style="position:relative;overflow:hidden;">
            <div class="ahsc_cache_warmer_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                    <span class="loader" style="position: relative;"></span>
                </div>
            </div>
			<div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'Cache preload', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></h1>
                <!--span class="ahsc_cache_warmer_loader loader" style="float: right;top: -30px;right: 15px;"></span-->
			</div>
			<legend style="display:inline-block">
				<?php
				echo  wp_kses( __( 'Cache preload is the process by which web pages are preloaded into the cache so that they can be displayed faster.The homepage and the most recent ten items will be automatically preloaded.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )
				?>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo  wp_kses( __( 'Cache preload is the process by which web pages are preloaded into the cache so that they can be displayed faster.The homepage and the most recent ten items will be automatically preloaded.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>

				<label for="ahsc_apc" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo  wp_kses( __( 'Enable cache preload', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
					<?php
					$this->option[ 'ahsc_cache_warmer' ]= (isset($this->option[ 'ahsc_cache_warmer' ]))?$this->option[ 'ahsc_cache_warmer' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_cache_warmer']['default'];
					?>
					<label class="switch" style="float:right">
						<input
							type="checkbox"
							value="1"
							name="ahsc_cache_warmer"
							id="ahsc_cache_warmer"
							<?php echo esc_html( ($this->option[ 'ahsc_cache_warmer' ])?"checked":""); ?>
						/>

						<span class="slider round"></span>
					</label>
				</label>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>