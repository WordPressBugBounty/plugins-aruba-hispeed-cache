jQuery(document).ready(function(){
    if(jQuery('#ahsc_debug_disable')){
        jQuery('#ahsc_debug_disable').on('click',function (e,o){
            $button=this;
            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : AHSC_DEBUG_OPTIONS_CONFIGS.ahsc_ajax_url,
                data : {action: "ahsc_disable_debug",ahsc_nonce:AHSC_DEBUG_OPTIONS_CONFIGS.ahsc_nonce},
                success: function(response) {
                    //console.log("response:"+response)
                    jQuery($button).remove();
                    jQuery("#ahsc_debug_status_message").html(AHSC_DEBUG_OPTIONS_CONFIGS.asc_result_message)
                    jQuery("#ahsc_debug_status_message").parent('.notice').removeClass("notice-warning");
                    jQuery("#ahsc_debug_status_message").parent('.notice').addClass("notice-success");
                    setTimeout(function () {
                        jQuery("#ahsc_debug_status_message").parent('.notice').fadeOut('fast');
                    }, 1000);
                }
            });
        })
    }
})