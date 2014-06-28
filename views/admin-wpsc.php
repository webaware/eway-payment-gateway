<?php
// custom fields for WP e-Commerce admin page

$eway_stored = get_option('wpsc_merchant_eway_stored') ? '1' : '0';
$eway_test = get_option('eway_test') ? '1' : '0';
$eway_th = get_option('wpsc_merchant_eway_th') ? '1' : '0';
$eway_beagle = get_option('wpsc_merchant_eway_beagle') ? '1' : '0';

?>

	<tr>
		<td>eWAY Customer ID</td>
		<td>
			<input type='text' size='10' value="<?php echo esc_attr(get_option('ewayCustomerID_id')); ?>" name='ewayCustomerID_id' />
		</td>
	</tr>

	<tr>
		<td>Use <a href='http://www.eway.com.au/how-it-works/payment-products#stored-payments' target="_blank">Stored Payments</a></td>
		<td>
			<label><input type='radio' value='1' name='eway_stored' <?php checked($eway_stored, '1'); ?> /> <?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<label><input type='radio' value='0' name='eway_stored' <?php checked($eway_stored, '0'); ?> /> <?php echo TXT_WPSC_NO; ?></label>
		</td>
	</tr>
	<tr id="wpsc-eway-admin-stored-test">
		<td colspan='2' style='color:#c00'>
			Stored Payments uses the Direct Payments sandbox;
			<br />there is no Stored Payments sandbox.
		</td>
	</tr>

	<tr>
		<td>Use Testing Enviroment</td>
		<td>
			<label><input type='radio' value='1' name='eway_test' <?php checked($eway_test, '1'); ?> /> <?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<label><input type='radio' value='0' name='eway_test' <?php checked($eway_test, '0'); ?> /> <?php echo TXT_WPSC_NO; ?></label>
		</td>
	</tr>

	<tr>
		<td>Use TH for field labels</td>
		<td>
			<label><input type='radio' value='1' name='eway_th' <?php checked($eway_th, '1'); ?> /> <?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<label><input type='radio' value='0' name='eway_th' <?php checked($eway_th, '0'); ?> /> <?php echo TXT_WPSC_NO; ?></label>
		</td>
	</tr>

	<tr>
		<td>Use <a href="http://www.eway.com.au/developers/resources/beagle-(free)-rules" target="_blank">Beagle</a></td>
		<td>
			<label><input type='radio' value='1' name='eway_beagle' <?php checked($eway_beagle, '1'); ?> /> <?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<label><input type='radio' value='0' name='eway_beagle' <?php checked($eway_beagle, '0'); ?> /> <?php echo TXT_WPSC_NO; ?></label>
			<span id="wpsc-eway-admin-beagle-address">
				<br />You will also need to add a Country field to your checkout form. Beagle works by comparing the country of the address with the
				country where the purchaser is using the Internet; Beagle won't be used when checking out without a country selected.
			</span>
		</td>
	</tr>
	<tr id="wpsc-eway-admin-stored-beagle">
		<td colspan='2' style='color:#c00'>
			Beagle is not available for Stored Payments
		</td>
	</tr>

	<tr>
		<td>Credit card message</td>
		<td>
			<input type="text" style="width:100%" value="<?php echo esc_attr(get_option('wpsc_merchant_eway_card_msg')); ?>" name="eway_card_msg" />
		</td>
	</tr>

	<tr>
		<td>First Name</td>
		<td>
			<select name='eway_form[first_name]'>
				<?php echo nzshpcrt_form_field_list(get_option('eway_form_first_name')); ?>
			</select>
		</td>
	</tr>

	<tr>
		<td>Last Name</td>
		<td>
			<select name='eway_form[last_name]'>
				<?php echo nzshpcrt_form_field_list(get_option('eway_form_last_name')); ?>
			</select>
		</td>
	</tr>

	<tr>
		<td>Address Field</td>
		<td>
			<select name='eway_form[address]'>
				<?php echo nzshpcrt_form_field_list(get_option('eway_form_address')); ?>
			</select>
		</td>
	</tr>

	<tr>
		<td>City Field</td>
		<td>
			<select name='eway_form[city]'>
				<?php echo nzshpcrt_form_field_list(get_option('eway_form_city')); ?>
			</select>
		</td>
	</tr>

	<tr>
		<td>State Field</td>
		<td>
			<select name='eway_form[state]'>
				<?php echo nzshpcrt_form_field_list(get_option('eway_form_state')); ?>
			</select>
		</td>
	</tr>

	<tr>
		<td>Postal code/Zip code Field</td>
		<td>
			<select name='eway_form[post_code]'>
				<?php echo nzshpcrt_form_field_list(get_option('eway_form_post_code')); ?>
			</select>
		</td>
	</tr>

	<tr>
		<td>Country Field</td>
		<td>
			<select name='eway_form[country]'>
				<?php echo nzshpcrt_form_field_list(get_option('eway_form_country')); ?>
			</select>
		</td>
	</tr>

	<tr>
		<td>Email Field</td>
		<td>
			<select name='eway_form[email]'>
				<?php echo nzshpcrt_form_field_list(get_option('eway_form_email')); ?>
			</select>
		</td>
	</tr>

<script>
(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function setVisibility() {
		var	useTest = ($("input[name='eway_test']:checked").val() === "1"),
			useBeagle = ($("input[name='eway_beagle']:checked").val() === "1"),
			useStored = ($("input[name='eway_stored']:checked").val() === "1");

		function display(element, visible) {
			if (visible)
				element.css({display: "none"}).show(750);
			else
				element.hide();
		}

		display($("#wpsc-eway-admin-stored-test"), (useTest && useStored));
		display($("#wpsc-eway-admin-stored-beagle"), (useBeagle && useStored));
		display($("#wpsc-eway-admin-beagle-address"), useBeagle);
	}

	$("#gateway_settings_wpsc_merchant_eway_form").on("change", "input[name='eway_test'],input[name='eway_beagle'],input[name='eway_stored']", setVisibility);

	setVisibility();

})(jQuery);
</script>

