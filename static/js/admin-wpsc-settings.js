(function ($) {
  function display(element, visible) {
    if (visible) element.show();else element.hide();
  }
  /**
  * check whether both the sandbox (test) mode and Stored Payments are selected,
  * show warning message if they are
  */


  function setVisibility() {
    var useTest = $("input[name='eway_test']:checked").val() === "1";
    display($(".eway_sandbox_field_row"), useTest);
  }

  $("#wpsc_options_page").on("change", "input[name='eway_test']", setVisibility); // watch for AJAX load of our form

  $(document).ajaxSuccess(function (event, xhr, settings) {
    if (settings.data.indexOf("wpsc_merchant_eway") !== -1) {
      setVisibility();
    }
  }); // watch for page load of our tab / form

  $(WPSC_Settings_Page).on("wpsc_settings_tab_loaded_gateway", setVisibility);
})(jQuery);
