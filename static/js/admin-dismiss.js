(function ($) {
  $("button.eway-payment-gateway-notice-dismiss").on("click", function (event) {
    var button = event.target;
    var dismiss = button.dataset.dismiss;
    var nonce = button.dataset.nonce;

    function closeNotice(response) {
      if (response.success && response.data.status) {
        $(button).closest(".is-dismissible").hide();
      } else {
        showError();
      }
    }

    if (dismiss) {
      var data = {
        action: "eway_payment_gateway_dismiss",
        dismiss: dismiss,
        nonce: nonce
      };
      $.getJSON(ajaxurl, data).done(closeNotice).fail(showError);
    }
  });

  function showError() {
    window.alert("Dismiss failed.");
  }
})(jQuery);
