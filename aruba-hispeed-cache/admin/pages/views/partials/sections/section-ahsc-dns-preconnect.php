<table class="form-table ahsc-table-ahsc_dns_preconnect ahsc_dns_preconnect">
	<tbody>
	<tr class="ahsc_dns_preconnect">
        <td style="position:relative;overflow:hidden;">
            <div class="ahsc_dns_preconnect_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                    <span class="loader" style="position: relative;"></span>
                </div>
            </div>
			<div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'DNS Prefetch and Preconnect:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></h1>
                <!--span class="ahsc_dns_preconnect_loader loader" style="float: right;top: -30px;right: 15px;"></span-->
			</div>
			<legend style="display:inline-block">
				<span><?php echo  wp_kses( __( 'DNS Prefetch and Preconnect are used to reduce the time to establish a connection to external resources, like CSS, fonts, js from some third-party domain.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo  wp_kses( __( 'DNS Prefetch and Preconnect are used to reduce the time to establish a connection to external resources, like CSS, fonts, js from some third-party domain.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>


				<label for="ahsc_dns_preconnect" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo wp_kses( __( 'Enable DNS Prefetch and Preconnect for external domain resources', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
					<?php
					$this->option[ 'ahsc_dns_preconnect' ]= (isset($this->option[ 'ahsc_dns_preconnect' ]))?$this->option[ 'ahsc_dns_preconnect' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_dns_preconnect']['default'];
					?>
					<label class="switch" style="float:right">
						<input
							type="checkbox"
							value="1"
							name="ahsc_dns_preconnect"
							id="ahsc_dns_preconnect"
							<?php echo esc_html( ($this->option[ 'ahsc_dns_preconnect' ])?"checked":""); ?>
						/>
						<span class="slider round"></span>
					</label>
				</label>

                <div id="<?php echo esc_html("ahsc_dns_preconnect_contenitor") ?>"  disabled="true" style="padding-top:10px;padding-bottom:10px">
					<?php echo wp_kses( __( 'DNS Prefetch and Preconnect domain list.', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ); ?>
                    <label for="<?php echo 'ahsc_dns_preconnect_domains' ?>">
	                    <?php
	                    $this->option[ 'ahsc_dns_preconnect_domains' ]= (isset($this->option[ 'ahsc_dns_preconnect_domains' ]))?$this->option[ 'ahsc_dns_preconnect_domains' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_dns_preconnect_domains']['default'];
	                    ?>
                            <div contenteditable="true" id="ahsc_dns_preconnect_domains"
                                      name="ahsc_dns_preconnect_domains" class="changeable" style="border:solid 1px #ccc;padding:6px;"><?php

	                            if(isset($this->option[ 'ahsc_dns_preconnect_domains' ]) && (($this->option[ 'ahsc_dns_preconnect_domains' ])|| is_array($this->option[ 'ahsc_dns_preconnect_domains' ])) ){
		                            foreach($this->option[ 'ahsc_dns_preconnect_domains' ] as $ahsc_preconnect_domain){
			                            echo "<div>".esc_url($ahsc_preconnect_domain,array(
					                            'https'
				                            ))."</div>";
		                            }
	                            }?>
                            </div>
                    </label>
                    <small><?php
						echo  wp_kses( __( 'Insert one external domain per line, for example "https://dominioesterno.it"', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )

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