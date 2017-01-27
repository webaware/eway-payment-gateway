(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function setVisibility() {
		var	useTest   = ($("#woocommerce_eway_payments_eway_sandbox:checked").val() === "1"),
			useStored = ($("#woocommerce_eway_payments_eway_stored:checked").val()  === "1");

		function display(element, visible) {
			if (visible)
				element.show();
			else
				element.hide();
		}

		display($("#woocommerce_eway_payments_eway_sandbox_api_key,#woocommerce_eway_payments_eway_sandbox_password,#woocommerce_eway_payments_eway_sandbox_ecrypt_key").closest("tr"), useTest);
		display($("#woocommerce-eway-admin-stored-test"), (useTest && useStored));
	}

	setVisibility();

	$("#mainform").on("change", "#woocommerce_eway_payments_eway_sandbox,#woocommerce_eway_payments_eway_stored", setVisibility);

	/**
	* enable the eWAY site seal code input
	*/
	$("#woocommerce_eway_payments_eway_site_seal").on("change", function() {
		var codeRow = $("#woocommerce_eway_payments_eway_site_seal_code").closest("tr");

		if (this.checked)
			codeRow.show();
		else
			codeRow.hide();
	}).trigger("change");

})(jQuery);
