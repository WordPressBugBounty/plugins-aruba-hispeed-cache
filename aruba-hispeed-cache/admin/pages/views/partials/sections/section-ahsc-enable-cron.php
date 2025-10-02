<table class="form-table ahsc-table-ahsc_lazy_load ahsc_lazy_load">
	<tbody>
	<tr class="ahsc_enable_cron">
		<td style="position:relative;overflow:hidden;">
            <div class="ahsc_enable_cron_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                <span class="loader" style="position: relative;"></span>
                </div>
            </div>
			<div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'WP-Cron', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></h1>
				<!--span class="ahsc_enable_cron_loader loader" style="float: right;top: -30px;right: 15px;"></span-->
			</div>
			<legend style="display:inline-block">
				<span><?php echo wp_kses( __( 'Schedule automatic tasks and jobs, at specific times.By setting up a frequency of jobs and tasks, like changing or publishing articles and pages, they will run at the required time, replacing any existing date and time scheduled on WordPress.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo wp_kses( __( 'Schedule automatic tasks and jobs, at specific times.By setting up a frequency of jobs and tasks, like changing or publishing articles and pages, they will run at the required time, replacing any existing date and time scheduled on WordPress.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>


				<label for="ahsc_enable_cron" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo  wp_kses( __( 'Enable Wp-Cron', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array()))
                        ?>
                    </span>
					<?php
					$this->option[ 'ahsc_enable_cron' ]= (isset($this->option[ 'ahsc_enable_cron' ]))?$this->option[ 'ahsc_enable_cron' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_enable_cron']['default'];
					?>
					<label class="switch" style="float:right">
						<input
							type="checkbox"
							value="1"
							name="ahsc_enable_cron"
							id="ahsc_enable_cron"
							<?php echo esc_html( ($this->option[ 'ahsc_enable_cron' ])?"checked":""); ?>
						/>
						<span class="slider round"></span>
					</label>
				</label>
                <div id="ahsc_cron_status_contenitor" style="p">
                    <div><h2 style="text-align: left;padding: 0px !important;margin-top: 0px !important;"><?php echo  wp_kses( __( 'Frequency of jobs', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )?></h2> </div>
					<?php
					$this->option[ 'ahsc_cron_time' ]= (isset($this->option[ 'ahsc_cron_time' ]))?$this->option[ 'ahsc_cron_time' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_cron_time']['default'];
					?>
                    <div class="ahsc_cron_time_button-group button-group" style="z-index: 0;">
                        <a href="#" class="ahsc_cron_time button button-secondary <?php echo (($this->option['ahsc_cron_time']==="300")?"active":"" ) ?>" data-value="300" >5 min</a>
                        <a href="#" class="ahsc_cron_time button button-secondary <?php echo (($this->option['ahsc_cron_time']==="900")?"active":"" ) ?>" data-value="900" >15 min</a>
                        <a href="#" class="ahsc_cron_time button button-secondary <?php echo (($this->option['ahsc_cron_time']==="3600")?"active":"" ) ?>" data-value="3600" >60 min</a>
                        <a href="#" class="ahsc_cron_time button button-secondary <?php echo (($this->option['ahsc_cron_time']==="7200")?"active":"" ) ?>" data-value="7200">120 min</a>
                        <a href="#" class="ahsc_cron_time button button-secondary <?php echo (($this->option['ahsc_cron_time']==="10800")?"active":"" ) ?>" data-value="10800">180 min</a>
                    </div>
                    <div>
                    <small><?php
						echo  wp_kses( __( 'Setup the time after which the job needs to be repeated.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
						?></small>
                    </div>
                </div>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>