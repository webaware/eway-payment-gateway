
// script supporting eWAY's Client Side Encryption

(function($) {

	var checkout = $(eway_ecrypt_vars.form);

	/**
	* basic card number validation using Luhn algorithm
	* @param {String} card_number
	* @return bool
	*/
	function cardnumberValid(card_number) {
		var checksum	= 0;
		var multiplier	= 1;
		var digit;

		// process each character, starting at the right
		for (var i = card_number.length - 1; i >= 0; i--) {
			digit = card_number.charAt(i) * multiplier;
			multiplier = (multiplier === 1) ? 2 : 1;

			// digit can't be greater than 9
			if (digit >= 10) {
				checksum++;
				digit -= 10;
			}

			checksum += digit;
		}

		return checksum % 10 === 0;
	}

	/**
	* if form field has a value, add encrypted hidden field and remove plain-text value from form
	* @param {String} selector
	* @param {Object} fieldspec encrypted field specification
	*/
	function maybeEncryptField(selector, fieldspec) {
		var field = checkout.find(selector);

		if (field.length) {
			var value = field.val().replace(/[\s-]/g, "");

			if (value.length) {
				if (fieldspec.is_cardnum && !cardnumberValid(value)) {
					throw {
						name:		"Credit Card Error",
						message:	eway_ecrypt_msg.card_number_invalid,
						field:		field,
					};
				}

				var encrypted = eCrypt.encryptValue(value, eway_ecrypt_vars.key);
				checkout.find("input[name='" + fieldspec.name + "']").remove();
				$("<input type='hidden'>").attr("name", fieldspec.name).val(encrypted).appendTo(checkout);
				field.val("");
			}
		}
	}

	/**
	* process all form fields that might require encryption
	* @param {jQuery.event} event
	*/
	function processFields(event) {
		var fields = eway_ecrypt_vars.fields;

		try {
			for (var i in fields) {
				maybeEncryptField(i, fields[i]);
			}
		}
		catch (e) {
			event.preventDefault();
			e.field.focus();
			window.alert(e.message);
			return false;
		}

		return true;
	}

	/**
	* get form element current value
	* @param {String} name
	* @return {String}
	*/
	function elementValue(name) {
		return checkout.get(0).elements[name].value;
	}


	switch (eway_ecrypt_vars.mode) {

		case "woocommerce":
			checkout.on("checkout_place_order_eway_payments", processFields);
			break;

		case "wp-e-commerce":
			checkout.on("submit", function(event) {
				if (elementValue("custom_gateway") === "wpsc_merchant_eway") {
					processFields(event);
				}
			});
			break;

		case "events-manager":
			checkout.on("submit", function(event) {
				if (elementValue("gateway") === "eway") {
					processFields(event);
				}
			});
			break;

		case "awpcp":
			checkout.on("submit", processFields);
			break;

	}

})(jQuery);
