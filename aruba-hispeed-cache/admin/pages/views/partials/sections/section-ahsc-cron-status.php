<table class="form-table ahsc-table-ahsc_cron_status ahsc_cron_status">
	<tbody>
	<tr class="ahsc_cron_status">
		<td>
			<div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'Manage WP-Cron schedule', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )  ?></h1>
                <span class="ahsc_cron_status_loader loader" style="float: right;top: -30px;right: 15px;"></span>
			</div>
			<legend style="display:inline-block">
				<span><?php echo wp_kses( __( 'It allows you to schedule the automatic execution of some tasks, in specific time intervals. By setting the execution frequency, all operations, such as editing or publishing posts and pages, will be performed in the chosen interval, possibly replacing the day and time scheduled on WordPress.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></span>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo wp_kses( __( 'It allows you to schedule the automatic execution of some tasks, in specific time intervals. By setting the execution frequency, all operations, such as editing or publishing posts and pages, will be performed in the chosen interval, possibly replacing the day and time scheduled on WordPress.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></span>
				</legend>

				<label for="ahsc_cron_status" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo  wp_kses( __( 'Manage WP-Cron schedule', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
					<?php
					$this->option[ 'ahsc_cron_status' ]= (isset($this->option[ 'ahsc_cron_status' ]))?$this->option[ 'ahsc_cron_status' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_cron_status']['default'];
					?>
					<label class="switch" style="float:right">
						<input
							type="checkbox"
							value="1"
							name="ahsc_cron_status"
							id="ahsc_cron_status"
							<?php echo esc_html( ($this->option[ 'ahsc_cron_status' ])?"checked":""); ?>
						/>
						<span class="slider round"></span>
					</label>
				</label>
				<div id="ahsc_cron_status_contenitor" style="padding-top:10px;padding-bottom:10px">
                    <h1><?php echo  wp_kses( __( 'Frequency of execution', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )?></h1>

                    <?php
					$this->option[ 'ahsc_cron_time' ]= (isset($this->option[ 'ahsc_cron_time' ]))?$this->option[ 'ahsc_cron_time' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_cron_time']['default'];
					?>
                    <div class="ahsc_cron_time_button-group button-group">
                        <a href="#" class="ahsc_cron_time button button-secondary <?php echo (($this->option['ahsc_cron_time']==="300")?"active":"" ) ?>" data-value="300" >5 min</a>
						<a href="#" class="ahsc_cron_time button button-secondary <?php echo (($this->option['ahsc_cron_time']==="900")?"active":"" ) ?>" data-value="900" >15 min</a>
						<a href="#" class="ahsc_cron_time button button-secondary <?php echo (($this->option['ahsc_cron_time']==="3600")?"active":"" ) ?>" data-value="3600" >60 min</a>
						<a href="#" class="ahsc_cron_time button button-secondary <?php echo (($this->option['ahsc_cron_time']==="7200")?"active":"" ) ?>" data-value="7200">120 min</a>
						<a href="#" class="ahsc_cron_time button button-secondary <?php echo (($this->option['ahsc_cron_time']==="10800")?"active":"" ) ?>" data-value="10800">180 min</a>
					</div>
					<small><?php
						echo  wp_kses( __( 'Set the time interval after which the activity should be repeated', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
						?></small>
				</div>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>
<script>
    /*var contents = jQuery('.changeable').html();
    jQuery('.changeable').blur(function() {
        if (contents!=jQuery(this).html()){
            alert('Handler for .change() called.');
            contents = jQuery(this).html();
        }
    });*/
</script>