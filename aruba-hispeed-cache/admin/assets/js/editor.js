( function ( wp, React ) {
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var Button   = wp.components.Button;
  //  var CheckboxControl= wp.components.CheckboxControl;


    var el = wp.element.createElement;
    var __ = wp.i18n.__;
//    var PluginSidebar = wp.editPost.PluginSidebar;


    function MyDocumentSettingPlugin() {
        return el(

            PluginDocumentSettingPanel,
            {
                name: 'my-document-setting-plugin',
                title: 'Aruba HiSpeed Cache',
                icon: 'database',
            },
            /*__( 'My Document Setting Panel Content', 'aruba-hispeed-cache' ),*/

            el(
                Button,
                {
                    className: 'ahsc-cleaner',
                    variant: 'secondary',
                    onClick:  ahscBtnPurger,
                },
                __( 'Cancella Cache', 'aruba-hispeed-cache' ),
            ),
            el('div',{ className: 'ahsc-loader'},"")

        );
    }

    registerPlugin( 'my-document-setting-plugin', {
        render: MyDocumentSettingPlugin,
    } );

    const ahscBtnPurger = async () => {
        if (typeof AHSC_TOOLBAR.ahsc_nonce == "undefined") {
            console.warn("No nonce is set for this action. This action has been aborted.");
            return;
        }

        document.getElementsByClassName('ahsc-loader')[0].style.fontWeight="Bold";
        //loader.style.display = "block";
        document.getElementsByClassName('ahsc-loader')[0].insertAdjacentHTML("beforeend", __( 'Cancellazione in corso', 'aruba-hispeed-cache' )  )
        let to_purge = "current-url" === AHSC_TOOLBAR.ahsc_topurge ? window.location.pathname : "all";

        const data = new FormData();
        data.append("action", "ahcs_clear_cache");
        data.append("ahsc_nonce", AHSC_TOOLBAR.ahsc_nonce);
        data.append("ahsc_to_purge", encodeURIComponent(to_purge));

        const request = await fetch(AHSC_TOOLBAR.ahsc_ajax_url, {
            method: "POST",
            credentials: "same-origin",
            body: data,
        })
            .then((r) => r.json())
            .then((result) => {
                if (result.code >= 200) {
                    let style = "";
                    let message_color="";
                    switch (result.type) {
                        case "success":
                            style = "color:green";
                            message_color="green";
                            break;
                        case "error":
                            style = "color:red";
                            message_color="red";
                            break;
                        default:
                            style = "color:blue";
                            message_color="blue";
                            break;
                    }
                    console.log(`%c${result.message}`, style);
                    document.getElementsByClassName('ahsc-loader')[0].innerHTML="";
                    document.getElementsByClassName('ahsc-loader')[0].style.color=message_color;
                    document.getElementsByClassName('ahsc-loader')[0].insertAdjacentHTML("beforeend", __( result.message, 'aruba-hispeed-cache' )  );
                    setTimeout(function() {
                        document.getElementsByClassName('ahsc-loader')[0].innerHTML="";
                    }, 3000); // <-- time in milliseconds

                }
            })
            .catch((error) => {
                console.log("[Aruba HiSpeed Cache Plugin]");
                console.error(error);
                document.getElementsByClassName('ahsc-loader')[0].innerHTML="";
                document.getElementsByClassName('ahsc-loader')[0].style.color=message_color;
                document.getElementsByClassName('ahsc-loader')[0].insertAdjacentHTML("beforeend",error);
            });

        return;
    };

    /* The line `window.ahscBtnPurger = ahscBtnPurger;` is assigning the `ahscBtnPurger` function to the
    `ahscBtnPurger` property of the `window` object. This makes the function accessible globally in the
    browser environment. */
    window.ahscBtnPurger = ahscBtnPurger;

} )( window.wp, window.React );