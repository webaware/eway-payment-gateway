<?php
// WooCommerce admin settings page
?>

<img style="float:right" src="<?php echo EwayPaymentsPlugin::getUrlPath(); ?>images/eway-siteseal.png" />
<h3><?php echo esc_html($this->admin_page_heading); ?></h3>
<p><?php echo esc_html($this->admin_page_description); ?></p>
<table class="form-table">
<?php $this->generate_settings_html(); ?>
</table>

<script>
(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function setVisibility() {
		var	useTest = ($("#woocommerce_eway_payments_eway_sandbox").filter(":checked").val() === "1"),
			useBeagle = ($("#woocommerce_eway_payments_eway_beagle").filter(":checked").val() === "1"),
			useStored = ($("#woocommerce_eway_payments_eway_stored").filter(":checked").val() === "1");

		function display(element, visible) {
			if (visible)
				element.css({display: "none"}).show(750);
			else
				element.hide();
		}

		display($("#woocommerce-eway-admin-stored-test"), (useTest && useStored));
		display($("#woocommerce-eway-admin-stored-beagle"), (useBeagle && useStored));
	}

	setVisibility();

	$("#mainform").on("change", "#woocommerce_eway_payments_eway_sandbox,#woocommerce_eway_payments_eway_beagle,#woocommerce_eway_payments_eway_stored", setVisibility);

	/**
	* enable the eWAY site seal code input
	*/
	$("#woocommerce_eway_payments_eway_site_seal").on("change", function() {
		var codeRow = $("#woocommerce_eway_payments_eway_site_seal_code").closest("tr");

		if (this.checked)
			codeRow.show(750);
		else
			codeRow.hide();
	}).trigger("change");

})(jQuery);
</script>
