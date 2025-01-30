(() => {
	("use strict");

	class AHSC_SETTINGS_PAGE {
		constructor(configs) {
			this.configs = configs;

			this.tabManger();
			this.purge();

			this.displayTabManer();
		}

		tabManger() {
			//console.log("tabManger");
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

		displayTabManer() {
			//console.log("display");
			let enable = document.querySelector("input#ahsc_enable_purge");
			let tabs = document.querySelectorAll("h2.ahsc-settings-nav a.nav-tab:not(.nav-tab-active)");
			let field = document.querySelectorAll(
				'form#ahsc-settings-form > [class*="ahsc-"]:not(.ahsc-table-general),.ahsc_cache_warmer'

			);

			enable.addEventListener("change", (e) => {
				//console.log("display changer");
				/*for (let i = 0; i < tabs.length; i++) {
					tabs[i].classList.toggle("hidden");
				}*/

				for (let j = 0; j < field.length; j++) {
					//console.log(field[j]);
					field[j].classList.toggle("hidden");
				}
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

					const data = new FormData();
					data.append("action", "ahcs_clear_cache");
					data.append("ahsc_nonce", this.configs.ahsc_nonce);
					data.append("ahsc_to_purge", this.configs.ahsc_topurge);

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
								console.log(`%c${esit.message}`, style);
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
	}

	window.addEventListener("load", () => {
		new AHSC_SETTINGS_PAGE(AHSC_OPTIONS_CONFIGS);
		jQuery("thead").find("th").on("click", function() {
			if(jQuery("div.chevron",this).hasClass('chevron-arrow-up')){
				jQuery("div.chevron",this).removeClass('chevron-arrow-up')
				jQuery("div.chevron",this).addClass('chevron-arrow-down')
			}else{
				jQuery("div.chevron",this).removeClass('chevron-arrow-down')
				jQuery("div.chevron",this).addClass('chevron-arrow-up')
			}

			jQuery(this).closest("table").find("tbody").toggle(); //you can set delay within toggle as well, like .toggle(500);
		});
	});

	//console.log('init:'+jQuery("#ahsc_dns_preconnect").is(':checked'))
	if(jQuery("#ahsc_dns_preconnect").is(':checked')==true) {
		jQuery("#ahsc_dns_preconnect_domains").prop("disabled", false);
	}else{
		jQuery("#ahsc_dns_preconnect_domains").prop("disabled", true);
	}
	jQuery("#ahsc_dns_preconnect").on('click',function(){
		//console.log(jQuery(this).is(':checked'))
		if(jQuery(this).is(':checked')==true) {
			jQuery("#ahsc_dns_preconnect_domains").prop("disabled", false);
		}else{
			jQuery("#ahsc_dns_preconnect_domains").prop("disabled", true);
		}
	})

	if(jQuery("#ahsc_cron_status").is(':checked')==true) {
		jQuery("#ahsc_cron_time").prop("disabled", false);
	}else{
		jQuery("#ahsc_cron_time").prop("disabled", true);
	}

	jQuery("#ahsc_cron_status").on('click',function(){

		if(jQuery(this).is(':checked')==true) {
			//jQuery("label[for='ahsc_cron_time']").hide();
			jQuery("#ahsc_cron_time").prop("disabled", false);
		}else{
			//jQuery("label[for='ahsc_cron_time']").show();
			jQuery("#ahsc_cron_time").prop("disabled", true);
		}
	})

})();
