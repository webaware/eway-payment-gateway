(function($) {

	$("button.eway-payment-gateway-notice-dismiss").on("click", function(event) {
		const button	= event.target;
		const dismiss	= button.dataset.dismiss;
		const nonce		= button.dataset.nonce;

		function closeNotice(response) {
			if (response.success && response.data.status) {
				$(button).closest(".is-dismissible").hide();
			}
			else {
				showError();
			}
		}

		if (dismiss) {
			const data = {
				action:		"eway_payment_gateway_dismiss",
				dismiss:	dismiss,
				nonce:		nonce,
			};
			$.getJSON(ajaxurl, data).done(closeNotice).fail(showError);
		}
	});

	function showError() {
		window.alert("Dismiss failed.");
	}

})(jQuery);
