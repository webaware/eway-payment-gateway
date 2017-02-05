
(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function setVisibility() {
		var	useTest   = ($("select[name='em_eway_mode']").val() === "sandbox"),
			useStored = ($("select[name='em_eway_stored']").val() === "1");

		function display(element, visible) {
			if (visible)
				element.show();
			else
				element.hide();
		}

		display($("#em-eway-admin-stored-test"), (useTest && useStored));
	}

	$("form[name='gatewaysettingsform']").on("change", "select[name='em_eway_mode'],select[name='em_eway_stored']", setVisibility);

	setVisibility();

})(jQuery);
