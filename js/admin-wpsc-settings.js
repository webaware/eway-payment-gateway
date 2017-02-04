(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function setVisibility() {
		var	useTest   = ($("input[name='eway_test']:checked").val()   === "1"),
			useBeagle = ($("input[name='eway_beagle']:checked").val() === "1"),
			useStored = ($("input[name='eway_stored']:checked").val() === "1");

		function display(element, visible) {
			if (visible)
				element.css({display: "none"}).show(750);
			else
				element.hide();
		}

		display($("#wpsc-eway-admin-stored-test"), (useTest && useStored));
		display($("#wpsc-eway-admin-stored-beagle"), (useBeagle && useStored));
		display($("#wpsc-eway-admin-beagle-address"), useBeagle);
	}

	$("#wpsc_options_page").on("change", "#gateway_settings_wpsc_merchant_eway_form input[name='eway_test'],#gateway_settings_wpsc_merchant_eway_form input[name='eway_beagle'],#gateway_settings_wpsc_merchant_eway_form input[name='eway_stored']", setVisibility);

	// watch for AJAX load of our form
	$(document).ajaxSuccess(function(event, xhr, settings) {
		if (settings.data.indexOf("wpsc_merchant_eway") !== -1) {
			setVisibility();
		}
	});

})(jQuery);
