<table class="form-table ahsc-table-ahsc_xmlrpc_status ahsc_xmlrpc_status">
	<tbody>
	<tr class="ahsc_xmlrpc_status">
        <td style="position:relative;overflow:hidden;">
            <div class="ahsc_xmlrpc_status_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                    <span class="loader" style="position: relative;"></span>
                </div>
            </div>
			<div  class="section-header" style="position: relative;display: block;height: 63px;">
				<h1> <?php echo wp_kses( __( 'XML-RPC', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )  ?></h1>
                <!--span class="ahsc_xmlrpc_status_loader loader" style="float: right;top: -30px;right: 15px;"></span-->
			</div>
			<legend style="display:inline-block">
				<span><?php echo  wp_kses( __( 'Enhance the protection of your website from cyber attacks by disabling the XML-RPC function that allows data transfer.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
			</legend>
			<fieldset >
				<legend class="screen-reader-text">
					<span><?php echo  wp_kses( __( 'Enhance the protection of your website from cyber attacks by disabling the XML-RPC function that allows data transfer.', 'aruba-hispeed-cache' ), array( 'strong' => array(), 'br' => array() ) )?></span>
				</legend>

				<label for="ahsc_xmlrpc_status" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo wp_kses( __( 'Disable XML-RPC', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
					<?php
					$this->option[ 'ahsc_xmlrpc_status' ]= (isset($this->option[ 'ahsc_xmlrpc_status' ]))?$this->option[ 'ahsc_xmlrpc_status' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_xmlrpc_status']['default'];
					?>
					<label class="switch" style="float:right">
						<input
							type="checkbox"
							value="1"
							name="ahsc_xmlrpc_status"
							id="ahsc_xmlrpc_status"
							<?php echo esc_html( ($this->option[ 'ahsc_xmlrpc_status' ])?"checked":""); ?>
						/>
						<span class="slider round"></span>
					</label>
				</label>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>