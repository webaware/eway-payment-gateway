// script supporting Eway's Client Side Encryption

(function ($) {
  const fields = eway_ecrypt_vars.fields;
  let checkout = $(eway_ecrypt_vars.form);

  /**
  * basic card number validation using Luhn algorithm
  * @param {String} card_number
  * @return bool
  */
  function cardnumberValid(card_number) {
    let checksum = 0;
    let multiplier = 1;

    // process each character, starting at the right
    for (let i = card_number.length - 1; i >= 0; i--) {
      let digit = card_number.charAt(i) * multiplier;
      multiplier = multiplier === 1 ? 2 : 1;

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
  * use ES6 String.prototype.repeat() if available, providing fallback for IE11
  * @param {String} character
  * @param {Number}
  * @return {String}
  */
  const repeatString = function () {
    if (typeof String.prototype.repeat === "function") {
      return (character, length) => {
        return character.repeat(length);
      };
    }
    return (character, length) => {
      let s = character;
      for (let i = 1; i < length; i++) {
        s += character;
      }
      return s;
    };
  }();

  /**
  * get placeholder mask for field
  * @param {String} mask_character
  * @param {Number} length
  * @param {bool} is_cardnum
  */
  function getPlaceholder(mask_character, length, is_cardnum) {
    if (is_cardnum) {
      const fragment = repeatString(mask_character, 4);
      return fragment + " " + fragment + " " + fragment + " " + fragment;
    }
    return repeatString(mask_character, length);
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
            name: "Credit Card Error",
            message: eway_ecrypt_msg.card_number_invalid,
            field: field
          };
        }
        const encrypted = eCrypt.encryptValue(value, eway_ecrypt_vars.key);
        const placeholder = getPlaceholder(eway_ecrypt_msg.ecrypt_mask, length, fieldspec.is_cardnum);
        checkout.find("input[name='" + fieldspec.name + "']").remove();
        $("<input type='hidden'>").attr("name", fieldspec.name).val(encrypted).appendTo(checkout);
        if (fieldspec.false_fill) {
          field.val(placeholder);
        } else {
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
    } catch (e) {
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
        } else {
          field.prop("placeholder", field.data("eway-old-placeholder"));
        }
      }
    });
  }

  /**
  * handle Events Manager form submit
  * @param {Event} event
  */
  function handleEventsManager(event) {
    if (elementValue("gateway") === "eway") {
      processFields(event);
    }
  }

  /**
  * handle changes in Events Manager Pro multiple-bookings checkout form when loaded via Ajax
  * @param {Event} event
  * @param {jqXHR} xhr
  * @param {Object} settings
  */
  function refreshEventsManagerCheckout(event, xhr, settings) {
    // only process Events Manager checkout page load
    if (settings.data.indexOf("em_checkout_page_contents") !== -1) {
      // refresh the checkout object, because the form has only just arrived via Ajax payload
      checkout = $(eway_ecrypt_vars.form);
      resetEncryptedFields();
      checkout.on("submit", handleEventsManager);
    }
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
        } catch (e) {
          SPCO.form_is_valid = false;
          window.event.preventDefault();
          e.field.focus();
          window.alert(e.message);
        }
      }
    });
  }
  switch (eway_ecrypt_vars.mode) {
    case "woocommerce":
      if (checkout.prop("id") === "order_review") {
        // customer payment page, from a link in an email
        checkout.on("submit", processFields);
      } else {
        // regular WooCommerce checkout page
        checkout.on("checkout_place_order_eway_payments", processFields);
      }
      $(document.body).on("checkout_error", resetEncryptedFields);
      break;
    case "wp-e-commerce":
      checkout.on("submit", function (event) {
        if (elementValue("custom_gateway") === "wpsc_merchant_eway") {
          processFields(event);
        }
      });
      break;
    case "events-manager":
      checkout.on("submit", handleEventsManager);
      $(document).on("em_checkout_page_refresh", function () {
        // watch for changes in Events Manager Pro multiple-bookings checkout form when loaded via Ajax
        $(document).ajaxComplete(refreshEventsManagerCheckout);
      });
      break;
    case "event-espresso":
      // must wait until Ready event, when Event Espresso single page checkout is available
      $(document).ready(function () {
        // process the form when the next step button is pressed
        SPCO.main_container.on("process_next_step_button_click", handleEventEspresso);
      });
      break;
    case "awpcp":
      checkout.on("submit", processFields);
      break;
  }
})(jQuery);
