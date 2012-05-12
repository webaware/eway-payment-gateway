<?php

/**
* admin functions for eWAY Payment Gateway
*/
class wpsc_merchant_eway_admin {

	/**
	* hook billing details display on admin, to show eWAY transaction number
	*/
	public static function actionBillingDetailsBottom() {
		global $purchlogitem;

		if (!empty($purchlogitem->extrainfo->transactid))
			echo '<p><strong>Transaction ID: ', htmlspecialchars($purchlogitem->extrainfo->transactid), "</strong></p>\n";
	}

	/**
	* display additional fields for gateway config form
	*/
	public static function configForm() {
		$eway_cvn = get_option('eway_cvn');
		$eway_cvn_yes = $eway_cvn ? 'checked="checked"' : '';
		$eway_cvn_no  = $eway_cvn ? '' : 'checked="checked"';

		$eway_test = get_option('eway_test');
		$eway_test_yes = $eway_test ? 'checked="checked"' : '';
		$eway_test_no  = $eway_test ? '' : 'checked="checked"';

		$eway_th = get_option('wpsc_merchant_eway_th');
		$eway_th_yes = $eway_th ? 'checked="checked"' : '';
		$eway_th_no  = $eway_th ? '' : 'checked="checked"';

		$ewayCustomerID = get_option('ewayCustomerID_id');
		$yes = TXT_WPSC_YES;
		$no = TXT_WPSC_NO;

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
		<td>Use Testing Enviroment</td>
		<td>
			<label><input type='radio' value='1' name='eway_test' $eway_test_yes /> $yes</label> &nbsp;
			<label><input type='radio' value='0' name='eway_test' $eway_test_no /> $no</label>
		</td>
	</tr>
	<tr>
		<td>Use CVN Security</td>
		<td>
			<label><input type='radio' value='1' name='eway_cvn' $eway_cvn_yes /> $yes</label> &nbsp;
			<label><input type='radio' value='0' name='eway_cvn' $eway_cvn_no /> $no</label>
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

EOT;
	}

	/**
	* save config details from payment gateway admin
	*/
	public static function saveConfig() {
		if ($_POST['ewayCustomerID_id'] != null) {
			update_option('ewayCustomerID_id', $_POST['ewayCustomerID_id']);
		}

		if ($_POST['eway_cvn'] != null) {
			update_option('eway_cvn', $_POST['eway_cvn']);
		}

		if ($_POST['eway_test'] != null) {
			update_option('eway_test', $_POST['eway_test']);
		}

		if ($_POST['eway_th'] != null) {
			update_option('wpsc_merchant_eway_th', $_POST['eway_th']);
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
		// add settings link
		if ($file == WPSC_MERCH_EWAY_PLUGIN_NAME) {
			$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CXNFEP4EAMTG6" title="Please consider making a donation to help support maintenance and further development of this plugin.">'
				. __('Donate') . '</a>';
		}

		return $links;
	}

}
