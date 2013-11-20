<?php
// WooCommerce admin settings page
?>

<img style="float:right" src="<?php echo EwayPaymentsPlugin::getUrlPath(); ?>images/eway-siteseal.png" />
<h3><?php echo htmlspecialchars($this->admin_page_heading); ?></h3>
<p><?php echo htmlspecialchars($this->admin_page_description); ?></p>
<table class="form-table">
<?php $this->generate_settings_html(); ?>
</table>

<script>
//<![CDATA[
jQuery(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function checkStoredSandbox() {
		var	useTest = ($("input[name='woocommerce_eway_payments_eway_sandbox']:checked").val() == "1"),
			useBeagle = ($("input[name='woocommerce_eway_payments_eway_beagle']:checked").val() == "1"),
			useStored = ($("input[name='woocommerce_eway_payments_eway_stored']:checked").val() == "1");

		if (useTest && useStored) {
			$("#woocommerce-eway-admin-stored-test").show(750);
		}
		else {
			$("#woocommerce-eway-admin-stored-test").hide();
		}

		if (useBeagle && useStored) {
			$("#woocommerce-eway-admin-stored-beagle").show(750);
		}
		else {
			$("#woocommerce-eway-admin-stored-beagle").hide();
		}
	}

	$("input[name='woocommerce_eway_payments_eway_sandbox'],input[name='woocommerce_eway_payments_eway_beagle'],input[name='woocommerce_eway_payments_eway_stored']").change(checkStoredSandbox);

	checkStoredSandbox();

});
//]]>
</script>
