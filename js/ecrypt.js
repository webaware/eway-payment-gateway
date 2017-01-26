// script supporting eWAY's Client Side Encryption

(function($) {

	var checkout = $(eway_ecrypt_vars.form);

	function maybeEncryptField(selector, fieldname) {
		var field = checkout.find(selector);

		if (field.length) {
			var value = field.val().replace(/[\s-]/g, "");

			if (value.length) {
				var encrypted = eCrypt.encryptValue(value, eway_ecrypt_vars.key);
				$("<input type='hidden'>").attr("name", fieldname).val(encrypted).appendTo(checkout);
				field.val("");
			}
		}
	}

	/**
	* watch for WooCommerce submit event
	*/
	checkout.on("checkout_place_order_eway_payments", function() {
		var fields = eway_ecrypt_vars.fields;
		for (var i in fields) {
			maybeEncryptField(i, fields[i]);
		}

		return true;
	});

})(jQuery);
