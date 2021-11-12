"use strict";

(function ($) {
  $("#awpcp-eway-checkout").submit(function (event) {
    var errors = [];
    var messages = eway_awpcp_checkout.errors;

    if ($("input[name='eway_card_number']").val().trim() === "") {
      errors.push(messages.card_number);
    }

    if ($("input[name='eway_card_name']").val().trim() === "") {
      errors.push(messages.card_name);
    }

    if ($("select[name='eway_expiry_month']").val() === "" || $("select[name='eway_expiry_year']").val() === "") {
      errors.push(messages.expiry_month);
    }

    if ($("input[name='eway_cvn']").val().trim() === "") {
      errors.push(messages.cvn);
    }

    if (errors.length > 0) {
      event.preventDefault();
      window.alert(errors.join("\n"));
    }
  });
})(jQuery);
