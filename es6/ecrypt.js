
// script supporting eWAY's Client Side Encryption

(function($) {

	const fields = eway_ecrypt_vars.fields;
	let checkout = $(eway_ecrypt_vars.form);

	/**
	* basic card number validation using Luhn algorithm
	* @param {String} card_number
	* @return bool
	*/
	function cardnumberValid(card_number) {
		let checksum	= 0;
		let multiplier	= 1;

		// process each character, starting at the right
		for (let i = card_number.length - 1; i >= 0; i--) {
			let digit = card_number.charAt(i) * multiplier;
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

	function repeatString(character, length) {
		let s = character;
		for (let i = 1; i < length; i++) {
			s += character;
		}
		return s;
	}

	/**
	* if form field has a value, add encrypted hidden field and remove plain-text value from form
	* @param {String} selector
	* @param {Object} fieldspec encrypted field specification
	*/
	function maybeEncryptField(selector, fieldspec) {
		const field = checkout.find(selector);

		if (field.length) {
			const value = field.val().replace(/[\s-]/g, "");
			const length = value.length;

			if (length) {
				if (fieldspec.is_cardnum && !cardnumberValid(value)) {
					throw {
						name:		"Credit Card Error",
						message:	eway_ecrypt_msg.card_number_invalid,
						field:		field,
					};
				}

				const encrypted = eCrypt.encryptValue(value, eway_ecrypt_vars.key);
				const placeholder = repeatString(eway_ecrypt_msg.ecrypt_mask, length);

				checkout.find("input[name='" + fieldspec.name + "']").remove();
				$("<input type='hidden'>").attr("name", fieldspec.name).val(encrypted).appendTo(checkout);

				if (fieldspec.false_fill) {
					field.val(placeholder);
				}
				else {
					field.val("").data("eway-old-placeholder", field.prop("placeholder")).prop("placeholder", placeholder);
				}
			}
		}
	}

	/**
	* process all form fields that might require encryption
	* @param {jQuery.event} event
	*/
	function processFields(event) {
		try {
			Object.keys(fields).forEach(selector => {
				maybeEncryptField(selector, fields[selector]);
			});
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
		let element = checkout.length ? checkout.get(0).elements[name] : false;
		return element ? element.value : false;
	}

	/**
	* reset the placeholders on WooCommerce checkout fields
	*/
	function resetEncryptedFields() {
		Object.keys(fields).forEach(selector => {
			let field = checkout.find(selector);
			if (field.length) {
				if (fields[selector].false_fill) {
					field.val("");
				}
				else {
					field.prop("placeholder", field.data("eway-old-placeholder"));
				}
			}
		});
	}

	/**
	* handle changes in Event Espresso's single page checkout form
	*/
	function handleEventEspresso() {
		// refresh checkout object, because form may have been destroyed after Ajax call
		checkout = $(eway_ecrypt_vars.form);

		Object.keys(fields).forEach(selector => {
			let field = $(selector);

			// if the field hasn't been encrypted yet, do so
			if (field.length > 0 && field.val().substring(0, 1) !== eway_ecrypt_msg.ecrypt_mask) {
				try {
					maybeEncryptField(selector, fields[selector]);
				}
				catch (e) {
					SPCO.form_is_valid = false;
					event.preventDefault();
					e.field.focus();
					window.alert(e.message);
				}
			}
		});
	}


	switch (eway_ecrypt_vars.mode) {

		case "woocommerce":
			checkout.on("checkout_place_order_eway_payments", processFields);
			$(document.body).on("checkout_error", resetEncryptedFields);
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

		case "event-espresso":
			// must wait until Ready event, when Event Espresso single page checkout is available
			$(document).ready(function() {
				// process the form when the next step button is pressed
				SPCO.main_container.on("process_next_step_button_click", handleEventEspresso);
			});
			break;

		case "awpcp":
			checkout.on("submit", processFields);
			break;

	}

})(jQuery);
