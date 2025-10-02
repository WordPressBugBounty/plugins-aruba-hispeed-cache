(() => {
	("use strict");

	class AHSC_SETTINGS_PAGE {
		constructor(configs) {
			//console.log(configs);
			this.configs = configs;

			this.tabManger();

			this.managePurge();
			this.manageCacheWarmer();
			this.manageStaticCache();
			this.manageApc();
			this.manageLazyLoad();
			this.manageHtmlOptimizer();
			this.managePreconnect();
			this.manageCronEnable();
			//this.manageCron();
			this.manageXMLRpc();
			this.manageDebugstatus();

			this.manageDBOptimization();

			this.manageReset();
			this.purge();

		}

		tabManger() {
			document.querySelectorAll(".ahsc-settings-nav a").forEach((item) => {
				item.addEventListener("click", (e) => {
					e.preventDefault();
					let nav_target = e.target;
					let nav_previus = document.querySelector(".ahsc-settings-nav a.nav-tab-active");
					let tab_target = e.target.dataset.tab;
					let tab_previus = nav_previus.dataset.tab;
					nav_target.classList.toggle("nav-tab-active");
					nav_previus.classList.toggle("nav-tab-active");
					document.querySelector("div" + tab_target).classList.toggle("hidden");
					document.querySelector("div" + tab_previus).classList.toggle("hidden");
				});
			});
		}
		async purge() {
			document.querySelector(".ahsc-actions-wrapper a#purgeall").addEventListener("click", async (e) => {
				e.preventDefault();

				//console.log(this.configs);
				if (typeof this.configs.ahsc_nonce == "undefined") {
					console.warn("No nonce is set for this action. This action has been aborted.");
					return;
				}

				if (confirm(this.configs.ahsc_confirm) === true) {
					// Loader
					const loader = document.createElement("div");
					loader.setAttribute("id", "ahsc-loader-toolbar");
					document.body.append(loader);
					loader.style.display = "block";
					//AHSC_TOOLBAR.ahsc_nonce
					const data = new FormData();
					data.append("action", "ahcs_clear_cache");
					data.append("ahsc_nonce", this.configs.ahsc_nonce);
					data.append("ahsc_to_purge", this.configs.ahsc_topurge);
console.log(" cancella cache -> "+this.configs.ahsc_ajax_url);

					const request = await fetch(this.configs.ahsc_ajax_url, {
						method: "POST",
						credentials: "same-origin",
						body: data,
					})
						.then((responde) => responde.json())
						.then((esit) => {
							if (esit.code >= 200) {
								let style = "";
								loader.style.removeProperty("display");
								switch (esit.type) {
									case "success":
										style = "color:green";
										break;
									case "error":
										style = "color:red";
										break;
									default:
										style = "color:blue";
										break;
								}
								//console.log(`%c${esit.message}`, style);
								jQuery(".ahsc-settings-header>h1").before('<div id="ahsc_chace_clear" class="notice notice-'+esit.type+' is-dismissible"><p>'+esit.message+'</p></div>');
								jQuery(document).scrollTop("#ahsc_chace_clear");
							}
						})
						.catch((error) => {
							console.log("[Aruba HiSpeed Cache Plugin]");
							console.error(error);
						});
				}

				return;
			});
		}
		async manageReset(){

				document.querySelector(".ahsc-actions-wrapper #ahsc_reset_save").addEventListener("click", async (e) => {
					e.preventDefault();
					if (confirm(this.configs.ahsc_reset_confirm) === true) {
						const loader = document.createElement("div");
						loader.setAttribute("id", "ahsc-loader-toolbar");
						document.body.append(loader);
						loader.style.display = "block";

						const data = new FormData();
						data.append("action", "ahsc_reset_options");

						const request = await fetch(this.configs.ahsc_ajax_url, {
							method: "POST",
							credentials: "same-origin",
							body: data,
						})
							.then((responde) => responde.json())
							.then((esit) => {
								//console.log(esit);
								jQuery(".ahsc-settings-header>h1").before('<div id="ahsc_chace_reset_options" class="notice notice-'+esit.type+' is-dismissible"><p>'+esit.message+' <a href="javascript:location.reload();">'+esit.action+'</a></p> </div>');
								jQuery(document).scrollTop("#ahsc_chace_reset_options");
								loader.style.display = "none";
							})
							.catch((error) => {
								console.log("[Aruba HiSpeed Cache Plugin]");
								console.error(error);
							});
					}
				});

		}


managePurge(){

		if (!jQuery("#ahsc_enable_purge").is(':checked')) {
			let field = document.querySelectorAll('#automatic-options,.ahsc_cache_warmer');
			for (let j = 0; j < field.length; j++) {
				field[j].classList.toggle("hidden");
			}
		}
	jQuery("#ahsc_enable_purge").on('click',function(event){
		event.preventDefault();
		//console.log("status: "+jQuery(this).is(':checked'));
		//console.log(jQuery('span.ahsc_enable_purge_loader'));
		var $ck=jQuery(this);
		jQuery('.ahsc_enable_purge_loader').css("display", "block");
		var $val=jQuery(this).is(':checked');
		jQuery.ajax({
			type : "post",
			dataType : "json",
			url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
			data : {action: "ahsc_enable_purge",status:$val},
			success: function(response) {
				//console.log("status : "+ response.result)
				$ck.prop( "checked", $val );
				jQuery('.ahsc_enable_purge_loader').css("display", "none");
				let field = document.querySelectorAll(
					'#automatic-options,.ahsc_cache_warmer'
				);
				for (let j = 0; j < field.length; j++) {
					//console.log(field[j]);
					field[j].classList.toggle("hidden");
				}
			}
		});
	});

	jQuery("#ahsc_purge_homepage_on_edit").on('click',function(event) {
		event.preventDefault();
		//console.log("status: " + jQuery(this).is(':checked'));
		var $ck=jQuery(this);
		jQuery('.ahsc_enable_purge_loader').css("display", "block");
		var $val=jQuery(this).is(':checked');
		jQuery.ajax({
			type : "post",
			dataType : "json",
			url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
			data : {action: "ahsc_purge_homepage_on_edit",status:$val},
			success: function(response) {
				//console.log("status : "+ response.result)
				$ck.prop( "checked", $val );
				jQuery('.ahsc_enable_purge_loader').css("display", "none");
			}
		});

	});

	jQuery("#ahsc_purge_page_on_new_comment").on('click',function(event) {
		event.preventDefault();
		//console.log("status: " + jQuery(this).is(':checked'));
		var $ck=jQuery(this);
		jQuery('.ahsc_enable_purge_loader').css("display", "block");
		var $val=jQuery(this).is(':checked');
		jQuery.ajax({
			type : "post",
			dataType : "json",
			url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
			data : {action: "ahsc_purge_page_on_new_comment",status:$val},
			success: function(response) {
				//console.log("status : "+ response.result)
				$ck.prop( "checked", $val );
				jQuery('.ahsc_enable_purge_loader').css("display", "none");
			}
		});

	});

	jQuery("#ahsc_purge_archive_on_edit").on('click',function(event) {
		event.preventDefault();
		//console.log("status: " + jQuery(this).is(':checked'));
		var $ck=jQuery(this);
		jQuery('.ahsc_enable_purge_loader').css("display", "block");
		var $val=jQuery(this).is(':checked');
		jQuery.ajax({
			type : "post",
			dataType : "json",
			url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
			data : {action: "ahsc_purge_archive_on_edit",status:$val},
			success: function(response) {
				//console.log("status : "+ response.result)
				$ck.prop( "checked", $val );
				jQuery('.ahsc_enable_purge_loader').css("display", "none");
			}
		});

	});
}

manageCacheWarmer(){
			//ahsc_cache_warmer
	jQuery("#ahsc_cache_warmer").on('click',function(event) {
		event.preventDefault();
		console.log("status: " + jQuery(this).is(':checked'));
		var $ck=jQuery(this);
		jQuery('.ahsc_cache_warmer_loader').css("display", "block");
		var $val=jQuery(this).is(':checked');
		jQuery.ajax({
			type : "post",
			dataType : "json",
			url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
			data : {action: "ahsc_cache_warmer",status:$val},
			success: function(response) {
				//console.log("status : "+ response.result)
				$ck.prop( "checked", $val );
				jQuery('.ahsc_cache_warmer_loader').css("display", "none");
			}
		});

	});

}

manageStaticCache(){
	//ahsc_static_cache
	jQuery("#ahsc_static_cache").on('click',function(event) {
		event.preventDefault();
		console.log("status: " + jQuery(this).is(':checked'));
		var $ck=jQuery(this);
		jQuery('.ahsc_static_cache_loader').css("display", "block");
		var $val=jQuery(this).is(':checked');
		jQuery.ajax({
			type : "post",
			dataType : "json",
			url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
			data : {action: "ahsc_static_cache",status:$val},
			success: function(response) {
				//console.log("status : "+ response.result)
				$ck.prop( "checked", $val );
				jQuery('.ahsc_static_cache_loader').css("display", "none");
			}
		});

	});
}


		manageApc() {
			jQuery("#ahsc_apc").on('click',function(event){
				event.preventDefault();

				console.log("APC status: "+jQuery(this).is(':checked'));
				var $ck=jQuery(this);
				jQuery('.ahsc_apc_loader').css("display", "block");
				if(jQuery(this).is(':checked')===true) {
					jQuery.ajax({
						type : "post",
						cache: false,
						dataType : "json",
						url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
						data : {action: "ahsc_check_apc_file"},
						success: function(response) {
							//console.log("check result : "+response.result );
							if(response.result === false) {
								if(jQuery("#ahsc-service-error").length===0) {
									jQuery(".ahsc-settings-header>h1").before(response.message);
									jQuery(document).scrollTop("#ahsc-service-error");
								}else{
									jQuery(document).scrollTop("#ahsc-service-error");
								}
								jQuery('.ahsc_apc_loader').css("display", "none");
								$ck.prop( "checked", false );
								return false;
							}else {
								//console.log("create file" );
								jQuery.ajax({
									type : "post",
									dataType : "json",
									cache: false,
									url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
									data : {action: "ahsc_create_apc_file"},
									success: function(response) {
										$ck.prop( "checked", true );
										jQuery.ajax({
											type: "post",
											dataType: "json",
											cache: false,
											url: AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
											data: {action: "ahsc_update_apc_Settings"},
											success: function(response) {
												//console.log("aggiorno status  " );
												jQuery('.ahsc_apc_loader').css("display", "none");
											}
										});
									}
								});
								return true;
							}
						}
					});
				}else{
					//console.log("chiamata per cancellazione file");
					jQuery.ajax({
						type : "post",
						cache: false,
						dataType : "json",
						url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
						data : {action: "ahsc_delete_apc_file"},
						success: function(response) {
							//console.log("cancello file : "+ response.result)
							$ck.prop( "checked", false );
							jQuery('.ahsc_apc_loader').css("display", "none");
						}
					});
				}
			});
		}

		manageLazyLoad(){
			//ahsc_lazy_load
			jQuery("#ahsc_lazy_load").on('click',function(event) {
				event.preventDefault();
				console.log("status: " + jQuery(this).is(':checked'));
				var $ck=jQuery(this);
				jQuery('.ahsc_lazy_load_loader').css("display", "block");
				var $val=jQuery(this).is(':checked');
				jQuery.ajax({
					type : "post",
					dataType : "json",
					url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
					data : {action: "ahsc_lazy_load",status:$val},
					success: function(response) {
						//console.log("status : "+ response.result)
						$ck.prop( "checked", $val );
						jQuery('.ahsc_lazy_load_loader').css("display", "none");
					}
				});

			});
		}
		manageHtmlOptimizer(){
			//ahsc_html_optimizer
			jQuery("#ahsc_html_optimizer").on('click',function(event) {
				event.preventDefault();
				console.log("status: " + jQuery(this).is(':checked'));
				var $ck=jQuery(this);
				jQuery('.ahsc_html_optimizer_loader').css("display", "block");
				var $val=jQuery(this).is(':checked');
				jQuery.ajax({
					type : "post",
					dataType : "json",
					url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
					data : {action: "ahsc_html_optimizer",status:$val},
					success: function(response) {
						//console.log("status : "+ response.result)
						$ck.prop( "checked", $val );
						jQuery('.ahsc_html_optimizer_loader').css("display", "none");
					}
				});

			});
		}

		managePreconnect(){
			if(jQuery("#ahsc_dns_preconnect").is(':checked')==true) {
				//jQuery("#ahsc_dns_preconnect_domains").prop("disabled", false);
				jQuery("#ahsc_dns_preconnect_contenitor").show();
			}else{
				//jQuery("#ahsc_dns_preconnect_domains").prop("disabled", true);
				jQuery("#ahsc_dns_preconnect_contenitor").hide();
			}

			jQuery("#ahsc_dns_preconnect").on('click',function(event) {
				event.preventDefault();
				console.log("status: " + jQuery(this).is(':checked'));
				var $ck=jQuery(this);
				jQuery('.ahsc_dns_preconnect_loader').css("display", "block");
				var $val=jQuery(this).is(':checked');
				jQuery.ajax({
					type : "post",
					dataType : "json",
					url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
					data : {action: "ahsc_dns_preconnect",status:$val},
					success: function(response) {
						//console.log("status : "+ response.result)
						$ck.prop( "checked", $val );
						jQuery('.ahsc_dns_preconnect_loader').css("display", "none");

						if(jQuery("#ahsc_dns_preconnect").is(':checked')==true) {
							//jQuery("#ahsc_dns_preconnect_domains").prop("disabled", false);
							jQuery("#ahsc_dns_preconnect_contenitor").show();

						}else{
							jQuery("#ahsc_dns_preconnect_contenitor").hide();
						}

					}
				});

			});
			var contents = jQuery('.changeable').html();
			jQuery('.changeable').blur(function() {
				if (contents!=jQuery(this).html()){
					jQuery('.ahsc_dns_preconnect_loader').css("display", "block");
					//alert('Handler for .change() called.');
					contents = jQuery(this).html();
					//alert(contents);
					jQuery.ajax({
						type : "post",
						dataType : "json",
						url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
						data : {action: "ahsc_dns_preconnect_domain_list",list:contents},
						success: function(response) {
							jQuery('.ahsc_dns_preconnect_loader').css("display", "none");
						}
					});

				}
			});
		}
		manageCronEnable(){


			if(jQuery("#ahsc_enable_cron").is(':checked')==true) {
				//jQuery("table.ahsc_cron_status").show();
				jQuery("#ahsc_cron_status_contenitor").show();
			}else{
				//jQuery("table.ahsc_cron_status").hide();
				jQuery("#ahsc_cron_status_contenitor").hide();
			}

			jQuery("#ahsc_enable_cron").on('click',function(event) {
				event.preventDefault();
				console.log("status: " + jQuery(this).is(':checked'));
				var $ck=jQuery(this);
				jQuery('.ahsc_enable_cron_loader').css("display", "block");
				var $val=jQuery(this).is(':checked');
				jQuery.ajax({
					type : "post",
					dataType : "json",
					url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
					data : {action: "ahsc_enable_cron",status:$val},
					success: function(response) {
						//console.log("status : "+ response.result)
						$ck.prop( "checked", $val );
						if(jQuery("#ahsc_enable_cron").is(':checked')==true) {
							//jQuery("table.ahsc_cron_status").show();
							jQuery("#ahsc_cron_status_contenitor").show();
						}else{
							//jQuery("table.ahsc_cron_status").hide();
							jQuery("#ahsc_cron_status_contenitor").hide();
						}
						jQuery('.ahsc_enable_cron_loader').css("display", "none");
					}
				});
			});

			var $time_change_status=false;
			jQuery("a.ahsc_cron_time").on('click',function(event) {
				event.preventDefault();
				if($time_change_status===false){
					$time_change_status=true;
					jQuery('.ahsc_enable_cron_loader').css("display", "block");
					var $bt=jQuery(this);
					var $val=jQuery(this).data('value');
					//console.log($val);
					jQuery.ajax({
						type : "post",
						dataType : "json",
						url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
						data : {action: "ahsc_cron_time",time:$val},
						success: function(response) {
							jQuery("a.ahsc_cron_time").removeClass('active');
							$bt.addClass('active');
							jQuery('.ahsc_enable_cron_loader').css("display", "none");
							$time_change_status=false;
						}
					});
				}
			});
		}

		manageXMLRpc(){
			//ahsc_xmlrpc_status
			jQuery("#ahsc_xmlrpc_status").on('click',function(event) {
				event.preventDefault();
				console.log("status: " + jQuery(this).is(':checked'));
				var $ck=jQuery(this);
				jQuery('.ahsc_xmlrpc_status_loader').css("display", "block");
				var $val=jQuery(this).is(':checked');
				jQuery.ajax({
					type : "post",
					dataType : "json",
					url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
					data : {action: "ahsc_xmlrpc_status",status:$val},
					success: function(response) {
						//console.log("status : "+ response.result)
						$ck.prop( "checked", $val );
						jQuery('.ahsc_xmlrpc_status_loader').css("display", "none");
					}
				});
			});
		}


		manageDebugstatus(){
			//ahsc_xmlrpc_status
			jQuery("#ahsc_debug_status").on('click',function(event) {
				event.preventDefault();
				//console.log("status: " + jQuery(this).is(':checked'));
				var $ck=jQuery(this);
				jQuery('.ahsc_debug_status_loader').css("display", "block");
				var $val=jQuery(this).is(':checked');
				jQuery.ajax({
					type : "post",
					dataType : "json",
					url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
					data : {action: "ahsc_debug_status",status:$val},
					success: function(response) {
						//console.log(response)
						$ck.prop( "checked", $val );
						jQuery('.ahsc_debug_status_loader').css("display", "none");
					}
				});
			});
		}


		manageDBOptimization(){
			jQuery("#ahsc-db-optimize").on('click',function(event) {
				event.preventDefault();
				//console.log("status: " + jQuery(this).is(':checked'));
				//var $ck=jQuery(this);
				jQuery('.ahsc_dboptimization_loader').css("display", "block");
				//var $val=jQuery(this).is(':checked');
				jQuery.ajax({
					type : "post",
					dataType : "json",
					url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
					data : {action: "ahsc_dboptimization",dbstatus:true},
					success: function(response) {
						//console.log(response)
						jQuery("#ahsc-db-status-indicator").removeClass('yellow');
						jQuery("#ahsc-db-status-indicator").addClass('green');
						jQuery("#ahsc-db-status-label").html(AHSC_OPTIONS_CONFIGS.ahsc_db_opt_status_active);
						jQuery("#ahsc-db-optimize-default").css("display","inline-block");
						jQuery("#ahsc-db-optimize").addClass('disabled');
						jQuery('.ahsc_dboptimization_loader').css("display", "none");
					}
				});
			});
			jQuery("#ahsc-db-optimize-default").on('click',function(event) {
				event.preventDefault();
				//console.log("status: " + jQuery(this).is(':checked'));
				//var $ck=jQuery(this);
				jQuery('.ahsc_dboptimization_loader').css("display", "block");
				//var $val=jQuery(this).is(':checked');
				jQuery.ajax({
					type : "post",
					dataType : "json",
					url : AHSC_OPTIONS_CONFIGS.ahsc_ajax_url,
					data : {action: "ahsc_dboptimization",dbstatus:false},
					success: function(response) {
						jQuery("#ahsc-db-status-indicator").removeClass('green');
						jQuery("#ahsc-db-status-indicator").addClass('yellow');
						jQuery("#ahsc-db-status-label").html(AHSC_OPTIONS_CONFIGS.ahsc_db_opt_status_disactive);
						jQuery("#ahsc-db-optimize").removeClass('disabled');
						jQuery("#ahsc-db-optimize-default").css("display","none");
						jQuery('.ahsc_dboptimization_loader').css("display", "none");
					}
				});
			});
		}

	}




	window.addEventListener("load", () => {
		new AHSC_SETTINGS_PAGE(AHSC_OPTIONS_CONFIGS);

	});
})();