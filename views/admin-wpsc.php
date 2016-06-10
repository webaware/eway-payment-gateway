<?php
// custom fields for WP eCommerce admin page

if (!defined('ABSPATH')) {
	exit;
}

$eway_stored	= get_option('wpsc_merchant_eway_stored') ? '1' : '0';
$eway_test		= get_option('eway_test')                 ? '1' : '0';
$eway_th		= get_option('wpsc_merchant_eway_th')     ? '1' : '0';
$eway_beagle	= get_option('wpsc_merchant_eway_beagle') ? '1' : '0';
$eway_logging	= get_option('eway_logging', 'off');

?>

	<tr valign="top">
		<th scope="row"><label for="ewayCustomerID_id">eWAY Customer ID</label></th>
		<td>
			<input type="text" size="10" value="<?php echo esc_attr(get_option('ewayCustomerID_id')); ?>" name="ewayCustomerID_id" id="ewayCustomerID_id" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_stored_label">Use Stored Payments</th>
		<td>
			<input type="radio" value="1" name="eway_stored" id="eway_stored_1" <?php checked($eway_stored, '1'); ?> aria-labelledby="eway_stored_label eway_stored_label_1" />
			<label for="eway_stored_1" id="eway_stored_label_1"><?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<input type="radio" value="0" name="eway_stored" id="eway_stored_0" <?php checked($eway_stored, '0'); ?> aria-labelledby="eway_stored_label eway_stored_label_0" />
			<label for="eway_stored_0" id="eway_stored_label_0"><?php echo TXT_WPSC_NO; ?></label>
		</td>
	</tr>
	<tr id="wpsc-eway-admin-stored-test" valign="top">
		<td colspan="2" style="color:#c00">
			Stored Payments uses the Direct Payments sandbox;
			<br />there is no Stored Payments sandbox.
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_test_label">Use Testing Enviroment</th>
		<td>
			<input type="radio" value="1" name="eway_test" id="eway_test_1" <?php checked($eway_test, '1'); ?> aria-labelledby="eway_test_label eway_test_label_1" />
			<label for="eway_test_1" id="eway_test_label_1"><?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<input type="radio" value="0" name="eway_test" id="eway_test_0" <?php checked($eway_test, '0'); ?> aria-labelledby="eway_test_label eway_test_label_0" />
			<label for="eway_test_0" id="eway_test_label_0"><?php echo TXT_WPSC_NO; ?></label>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_logging_label">Logging</th>
		<td>
			<input type="radio" value="off" name="eway_logging" id="eway_logging_off" <?php checked($eway_logging, 'off'); ?> aria-labelledby="eway_logging_label eway_logging_label_off" />
			<label for="eway_logging_off" id="eway_logging_label_off">Off</label> &nbsp;
			<input type="radio" value="info" name="eway_logging" id="eway_logging_info" <?php checked($eway_logging, 'info'); ?> aria-labelledby="eway_logging_label eway_logging_label_info" />
			<label for="eway_logging_info" id="eway_logging_label_info">All messages</label> &nbsp;
			<input type="radio" value="error" name="eway_logging" id="eway_logging_error" <?php checked($eway_logging, 'error'); ?> aria-labelledby="eway_logging_label eway_logging_label_error" />
			<label for="eway_logging_error" id="eway_logging_label_error">Errors only</label> &nbsp;
			<p class="description">enable logging to assist trouble shooting;
			<br />the log file can be found in <?php echo esc_html(substr(EwayPaymentsLogging::getLogFolder(), strlen(ABSPATH))); ?></p>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_th_label">Use TH for field labels</th>
		<td>
			<input type="radio" value="1" name="eway_th" id="eway_th_1" <?php checked($eway_th, '1'); ?> aria-labelledby="eway_th_label eway_th_label_1" />
			<label for="eway_th_1" id="eway_th_label_1"><?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<input type="radio" value="0" name="eway_th" id="eway_th_0" <?php checked($eway_th, '0'); ?> aria-labelledby="eway_th_label eway_th_label_0" />
			<label for="eway_th_0" id="eway_th_label_0"><?php echo TXT_WPSC_NO; ?></label>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_beagle_label">Use <a href="https://www.eway.com.au/developers/api/beagle-lite" target="_blank">Beagle</a></th>
		<td>
			<input type="radio" value="1" name="eway_beagle" id="eway_beagle_1" <?php checked($eway_beagle, '1'); ?> aria-labelledby="eway_beagle_label eway_beagle_label_1" />
			<label for="eway_beagle_1" id="eway_beagle_label_1"><?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<input type="radio" value="0" name="eway_beagle" id="eway_beagle_0" <?php checked($eway_beagle, '0'); ?> aria-labelledby="eway_beagle_label eway_beagle_label_0" />
			<label for="eway_beagle_0" id="eway_beagle_label_0"><?php echo TXT_WPSC_NO; ?></label>
			<span id="wpsc-eway-admin-beagle-address">
				<br />You will also need to add a Country field to your checkout form. Beagle works by comparing the country of the address with the
				country where the purchaser is using the Internet; Beagle won't be used when checking out without a country selected.
			</span>
		</td>
	</tr>
	<tr id="wpsc-eway-admin-stored-beagle" valign="top">
		<td colspan="2" style="color:#c00">
			Beagle is not available for Stored Payments
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="eway_card_msg">Credit card message</label></th>
		<td>
			<input type="text" style="width:100%" value="<?php echo esc_attr(get_option('wpsc_merchant_eway_card_msg')); ?>" name="eway_card_msg" id="eway_card_msg" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="eway_form_first_name">First Name</label></th>
		<td>
			<select name="eway_form[first_name]" id="eway_form_first_name">
				<?php EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_first_name')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="eway_form_last_name">Last Name</label></th>
		<td>
			<select name="eway_form[last_name]" id="eway_form_last_name">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_last_name')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="eway_form_address">Address Field</label></th>
		<td>
			<select name="eway_form[address]" id="eway_form_address">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_address')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="eway_form_city">City Field</label></th>
		<td>
			<select name="eway_form[city]" id="eway_form_city">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_city')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="eway_form_state">State Field</label></th>
		<td>
			<select name="eway_form[state]" id="eway_form_state">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_state')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="eway_form_post_code">Postal code/Zip code Field</label></th>
		<td>
			<select name="eway_form[post_code]" id="eway_form_post_code">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_post_code')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="eway_form_country">Country Field</label></th>
		<td>
			<select name="eway_form[country]" id="eway_form_country">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_country')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="eway_form_email">Email Field</label></th>
		<td>
			<select name="eway_form[email]" id="eway_form_email">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_email')); ?>
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

