(function ($) {
  function display(element, visible) {
    if (visible) element.show();else element.hide();
  }
  /**
  * check whether both the sandbox (test) mode and Stored Payments are selected,
  * show warning message if they are
  */


  function setVisibility() {
    const useTest = $("select[name='em_eway_mode']").val() === "sandbox";
    display($(".em_eway_sandbox_row"), useTest);
  }

  $("form[name='gatewaysettingsform']").on("change", "select[name='em_eway_mode']", setVisibility);
  setVisibility();
})(jQuery);
