(function ($) {
  function display(element, visible) {
    if (visible) element.show();else element.hide();
  }

  /**
  * check whether both the sandbox (test) mode and Stored Payments are selected,
  * show warning message if they are
  */
  function setVisibility() {
    const useTest = $("#woocommerce_eway_payments_eway_sandbox:checked").val() === "1";
    display($("#woocommerce_eway_payments_eway_sandbox_api_key,#woocommerce_eway_payments_eway_sandbox_password,#woocommerce_eway_payments_eway_sandbox_ecrypt_key").closest("tr"), useTest);
  }
  setVisibility();
  $("#mainform").on("change", "#woocommerce_eway_payments_eway_sandbox", setVisibility);

  /**
  * enable the Eway site seal code input
  */
  $("#woocommerce_eway_payments_eway_site_seal").on("change", function () {
    const codeRow = $("#woocommerce_eway_payments_eway_site_seal_code").closest("tr");
    if (this.checked) codeRow.show();else codeRow.hide();
  }).trigger("change");
})(jQuery);
