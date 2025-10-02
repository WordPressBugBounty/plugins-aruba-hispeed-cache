<?php
//$sections=$this->fields['sections']['general']['general']['ahsc_enable_purge'];
//var_export($this->option);
?>

<table class="form-table ahsc-table-ahsc_enable_purge ahsc_enable_purge">
    <tbody>
    <tr class="ahsc_enable_purge">
        <td style="position:relative;overflow:hidden;">
            <div class="ahsc_enable_purge_loader boxloader" style="height: 93%;width: 97%;position: absolute;z-index: 1;background: rgba(255, 255, 255, .5);">
                <div style=" display: flex;justify-content: center;align-items: center;height: 100%;width:100%;">
                    <span class="loader" style="position: relative;"></span>
                </div>
            </div>
            <div  class="section-header" style="position: relative;display: block;height: 63px;">
                <h1> <?php echo wp_kses( __( 'Automatically clear cache', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></h1>
                <!--span class="ahsc_enable_purge_loader loader" style="float: right;top: -30px;right: 15px;"></span-->
            </div>
            <fieldset >

                <legend class="screen-reader-text">
                    <span><?php echo wp_kses( __( 'Enable automatic cache clearing ', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ) ?></span>
                </legend>

                <label for="ahsc_enable_purge" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo wp_kses( __( 'Enable automatic cache clearing ', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
	                <?php

	                $this->option[ 'ahsc_enable_purge' ]= (isset($this->option[ 'ahsc_enable_purge' ]))?$this->option[ 'ahsc_enable_purge' ]:AHSC_OPTIONS_LIST_DEFAULT['ahsc_enable_purge']['default'];
	                ?>
                    <label class="switch" style="float:right">
                        <input
                                type="checkbox"
                                value="1"
                                name="ahsc_enable_purge"
                                id="ahsc_enable_purge"
			                <?php echo esc_html( ($this->option[ 'ahsc_enable_purge' ])?"checked":""); ?>
                        />

                        <span class="slider round"></span>
                    </label>
                </label>
            </fieldset>
            <div id="automatic-options">
                <hr style="border-bottom: solid 1px #DDDDDD">
            <h2><?php echo  wp_kses( __( 'Automatically clear whole cache when:', 'aruba-hispeed-cache' ), array( 'strong' => array() ) ); ?></h2>
            <fieldset>

                <label for="ahsc_purge_homepage_on_edit" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo wp_kses( __( 'An <strong>article</strong>, <strong>page</strong>, or <strong>personalized content</strong> is added, edited or deleted', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
                    <label class="switch" style="float:right">
                        <input
                                type="checkbox"
                                value="1"
                                name="ahsc_purge_homepage_on_edit"
                                id="ahsc_purge_homepage_on_edit"
				            <?php echo esc_html( ($this->option[ 'ahsc_purge_homepage_on_edit' ])?"checked":""); ?>
                        />

                        <span class="slider round"></span>
                    </label>
                </label>

                <label for="ahsc_purge_page_on_new_comment" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo wp_kses( __( 'A <strong>comment</strong> is entered, approved, declined, published or deleted in any part of the website', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
                    <label class="switch" style="float:right">
                        <input
                                type="checkbox"
                                value="1"
                                name="ahsc_purge_page_on_new_comment"
                                id="ahsc_purge_page_on_new_comment"
				            <?php echo esc_html( ($this->option[ 'ahsc_purge_page_on_new_comment' ])?"checked":""); ?>
                        />

                        <span class="slider round"></span>
                    </label>
                </label>

                <label for="ahsc_ahsc_purge_archive_on_edit" style="display: inline-block;position:relative;" >
                    <span style="float:left;height: 34px;">
                        <?php
                        echo wp_kses( __( 'A <strong>term</strong> or <strong>menu</strong> is edited, added or deleted', 'aruba-hispeed-cache' ), array( 'strong' => array() ) )
                        ?>
                    </span>
                    <label class="switch" style="float:right">
                        <input
                                type="checkbox"
                                value="1"
                                name="ahsc_purge_archive_on_edit"
                                id="ahsc_purge_archive_on_edit"
				            <?php echo esc_html( ($this->option[ 'ahsc_purge_archive_on_edit' ])?"checked":""); ?>
                        />

                        <span class="slider round"></span>
                    </label>
                </label>




            </fieldset>
            </div>
        </td>
    </tr>
    </tbody>
</table>