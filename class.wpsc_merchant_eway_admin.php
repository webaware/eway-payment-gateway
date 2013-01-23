<?php

/**
* admin functions for eWAY Payment Gateway
*/
class wpsc_merchant_eway_admin {

	/**
	* hook billing details display on admin, to show eWAY transaction number and authcode
	*/
	public static function actionBillingDetailsBottom() {
		global $purchlogitem;

		if (!empty($purchlogitem->extrainfo->transactid))
			echo '<p><strong>Transaction ID: ', htmlspecialchars($purchlogitem->extrainfo->transactid), "</strong></p>\n";
		if (!empty($purchlogitem->extrainfo->authcode))
			echo '<p><strong>Auth Code: ', htmlspecialchars($purchlogitem->extrainfo->authcode), "</strong></p>\n";
	}

	/**
	* display additional fields for gateway config form
	*/
	public static function configForm() {
		$eway_stored = get_option('wpsc_merchant_eway_stored');
		$eway_stored_yes = $eway_stored ? 'checked="checked"' : '';
		$eway_stored_no  = $eway_stored ? '' : 'checked="checked"';

		$eway_test = get_option('eway_test');
		$eway_test_yes = $eway_test ? 'checked="checked"' : '';
		$eway_test_no  = $eway_test ? '' : 'checked="checked"';

		$eway_th = get_option('wpsc_merchant_eway_th');
		$eway_th_yes = $eway_th ? 'checked="checked"' : '';
		$eway_th_no  = $eway_th ? '' : 'checked="checked"';

		$eway_beagle = get_option('wpsc_merchant_eway_beagle');
		$eway_beagle_yes = $eway_beagle ? 'checked="checked"' : '';
		$eway_beagle_no  = $eway_beagle ? '' : 'checked="checked"';

		$ewayCustomerID = get_option('ewayCustomerID_id');
		$yes = TXT_WPSC_YES;
		$no = TXT_WPSC_NO;

		$eway_form_first_name_fields = nzshpcrt_form_field_list(get_option('eway_form_first_name'));
		$eway_form_last_name_fields = nzshpcrt_form_field_list(get_option('eway_form_last_name'));
		$eway_form_address_fields = nzshpcrt_form_field_list(get_option('eway_form_address'));
		$eway_form_city_fields = nzshpcrt_form_field_list(get_option('eway_form_city'));
		$eway_form_state_fields = nzshpcrt_form_field_list(get_option('eway_form_state'));
		$eway_form_post_code_fields = nzshpcrt_form_field_list(get_option('eway_form_post_code'));
		$eway_form_country_fields = nzshpcrt_form_field_list(get_option('eway_form_country'));
		$eway_form_email_fields = nzshpcrt_form_field_list(get_option('eway_form_email'));

		return <<<EOT
	<tr>
		<td>eWAY Customer ID</td>
		<td>
			<input type='text' size='10' value="$ewayCustomerID" name='ewayCustomerID_id' />
		</td>
	</tr>
	<tr>
		<td>Use Stored Payments</td>
		<td>
			<label><input type='radio' value='1' name='eway_stored' $eway_stored_yes /> $yes</label> &nbsp;
			<label><input type='radio' value='0' name='eway_stored' $eway_stored_no /> $no</label>
		</td>
	</tr>
	<tr id="wpsc-eway-admin-stored-test">
		<td colspan='2' style='color:#c00'>
			Stored Payments use the Direct Payments sandbox;
			<br />there is no Stored Payments sandbox.
		</td>
	</tr>
	<tr>
		<td>Use Testing Enviroment</td>
		<td>
			<label><input type='radio' value='1' name='eway_test' $eway_test_yes /> $yes</label> &nbsp;
			<label><input type='radio' value='0' name='eway_test' $eway_test_no /> $no</label>
		</td>
	</tr>
	<tr>
		<td>Use TH for field labels</td>
		<td>
			<label><input type='radio' value='1' name='eway_th' $eway_th_yes /> $yes</label> &nbsp;
			<label><input type='radio' value='0' name='eway_th' $eway_th_no /> $no</label>
		</td>
	</tr>
	<tr>
		<td>Use <a href="http://www.eway.com.au/developers/resources/beagle-(free)-rules" target="_blank">Beagle</a></td>
		<td>
			<label><input type='radio' value='1' name='eway_beagle' $eway_beagle_yes /> $yes</label> &nbsp;
			<label><input type='radio' value='0' name='eway_beagle' $eway_beagle_no /> $no</label>
		</td>
	</tr>
	<tr id="wpsc-eway-admin-stored-beagle">
		<td colspan='2' style='color:#c00'>
			Beagle is not available for Stored Payments
		</td>
	</tr>

	<tr>
		<td>First Name</td>
		<td>
			<select name='eway_form[first_name]'>
				$eway_form_first_name_fields
			</select>
		</td>
	</tr>
	<tr>
		<td>Last Name</td>
		<td>
			<select name='eway_form[last_name]'>
				$eway_form_last_name_fields
			</select>
		</td>
	</tr>
	<tr>
		<td>Address Field</td>
		<td>
			<select name='eway_form[address]'>
				$eway_form_address_fields
			</select>
		</td>
	</tr>
	<tr>
		<td>City Field</td>
		<td>
			<select name='eway_form[city]'>
				$eway_form_city_fields
			</select>
		</td>
	</tr>
	<tr>
		<td>State Field</td>
		<td>
			<select name='eway_form[state]'>
				$eway_form_state_fields
			</select>
		</td>
	</tr>
	<tr>
		<td>Postal code/Zip code Field</td>
		<td>
			<select name='eway_form[post_code]'>
				$eway_form_post_code_fields
			</select>
		</td>
	</tr>
	<tr>
		<td>Country Field</td>
		<td>
			<select name='eway_form[country]'>
				$eway_form_country_fields
			</select>
		</td>
	</tr>
	<tr>
		<td>Email Field</td>
		<td>
			<select name='eway_form[email]'>
				$eway_form_email_fields
			</select>
		</td>
	</tr>

<script>
//<![CDATA[
jQuery(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function checkStoredSandbox() {
		var	useTest = ($("input[name='eway_test']:checked").val() == "1"),
			useBeagle = ($("input[name='eway_beagle']:checked").val() == "1"),
			useStored = ($("input[name='eway_stored']:checked").val() == "1");

		if (useTest && useStored) {
			$("#wpsc-eway-admin-stored-test").show(750);
		}
		else {
			$("#wpsc-eway-admin-stored-test").hide();
		}

		if (useBeagle && useStored) {
			$("#wpsc-eway-admin-stored-beagle").show(750);
		}
		else {
			$("#wpsc-eway-admin-stored-beagle").hide();
		}
	}

	$("input[name='eway_test'],input[name='eway_stored'],input[name='eway_beagle']").change(checkStoredSandbox);

	checkStoredSandbox();

});
//]]>
</script>

EOT;
	}

	/**
	* save config details from payment gateway admin
	*/
	public static function saveConfig() {
		if (isset($_POST['ewayCustomerID_id'])) {
			update_option('ewayCustomerID_id', $_POST['ewayCustomerID_id']);
		}

		if (isset($_POST['eway_stored'])) {
			update_option('wpsc_merchant_eway_stored', $_POST['eway_stored']);
		}

		if (isset($_POST['eway_test'])) {
			update_option('eway_test', $_POST['eway_test']);
		}

		if (isset($_POST['eway_th'])) {
			update_option('wpsc_merchant_eway_th', $_POST['eway_th']);
		}

		if (isset($_POST['eway_beagle'])) {
			update_option('wpsc_merchant_eway_beagle', $_POST['eway_beagle']);
		}

		foreach ((array)$_POST['eway_form'] as $form => $value) {
			update_option(('eway_form_'.$form), $value);
		}

		return true;
	}

	/**
	* action hook for adding plugin details links
	*/
	public static function addPluginDetailsLinks($links, $file) {
		if ($file == WPSC_MERCH_EWAY_PLUGIN_NAME) {
			$links[] = '<a href="http://wordpress.org/support/plugin/eway-payment-gateway">' . __('Get help') . '</a>';
			$links[] = '<a href="http://wordpress.org/extend/plugins/eway-payment-gateway/">' . __('Rating') . '</a>';
			$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=CXNFEP4EAMTG6">' . __('Donate') . '</a>';
		}

		return $links;
	}

}
