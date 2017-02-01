// script supporting eWAY's Client Side Encryption

(function($) {

	var checkout = $(eway_ecrypt_vars.form);

	/**
	* if form field has a value, add encrypted hidden field and remove plain-text value from form
	* @param {String} selector
	* @param {String} encrypted field name
	*/
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
	* process all form fields that might require encryption
	*/
	function processFields() {
		var fields = eway_ecrypt_vars.fields;
		for (var i in fields) {
			maybeEncryptField(i, fields[i]);
		}

		return true;
	}

	/**
	* watch for WooCommerce submit event
	*/
	checkout.on("checkout_place_order_eway_payments", processFields);

	/**
	* watch for AWPCP submit event
	*/
	if (eway_ecrypt_vars.form.indexOf("awpcp") !== -1) {
		checkout.on("submit", processFields);
	}

})(jQuery);
