/* The code is an immediately invoked function expression (IIFE) that creates a loader element and
defines an asynchronous function called `ahscBtnPurger`. */
(() => {
	("use strict");

	/* The code is creating a new `div` element and assigning it to the `loader` variable. It then sets the
	`id` attribute of the `div` element to "ahsc-loader-toolbar". Finally, it appends the `div` element
	to the `body` of the document. This code is creating a loader element that can be used to indicate
	that a process is in progress. */
	const loader = document.createElement("div");
	loader.setAttribute("id", "ahsc-loader-toolbar");
	document.body.append(loader);

	/**
	 * The above function is an asynchronous JavaScript function that purges the cache using an AJAX
	 * request.
	 * @returns nothing (undefined).
	 */
	const ahscBtnPurger = async () => {
		if (typeof AHSC_TOOLBAR.ahsc_nonce == "undefined") {
			console.warn("No nonce is set for this action. This action has been aborted.");
			return;
		}

		loader.style.display = "block";

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
					loader.style.removeProperty("display");
					switch (result.type) {
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
					console.log(`%c${result.message}`, style);
				}
			})
			.catch((error) => {
				console.log("[Aruba HiSpeed Cache Plugin]");
				console.error(error);
			});

		return;
	};

	/* The line `window.ahscBtnPurger = ahscBtnPurger;` is assigning the `ahscBtnPurger` function to the
	`ahscBtnPurger` property of the `window` object. This makes the function accessible globally in the
	browser environment. */
	window.ahscBtnPurger = ahscBtnPurger;

	const ahscBtnTransientPurger = async () => {
		loader.style.display = "block";



		const data = new FormData();
		data.append("action", "ahsc_clear_expired_transient");
		data.append("ahsc_nonce", AHSC_TOOLBAR.ahsc_nonce);

		const request = await fetch(AHSC_TOOLBAR.ahsc_ajax_url, {
			method: "POST",
			credentials: "same-origin",
			body: data,
		})
			.then((r) => r.json())
			.then((result) => {
				if (result.code >= 200) {
					let style = "";
					loader.style.removeProperty("display");
					switch (result.type) {
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
					console.log(`%c${result.message}`, style);
				}
			})
			.catch((error) => {
				console.log("[Aruba HiSpeed Cache Plugin]");
				console.error(error);
			});


	}

	window.ahscBtnTransientPurger = ahscBtnTransientPurger;

})();
