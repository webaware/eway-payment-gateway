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
		<th scope="row">
			<label for="eway_api_key"><?php echo esc_html_x('API key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" value="<?php echo esc_attr(get_option('eway_api_key')); ?>" name="eway_api_key" id="eway_api_key"
				style="width: 100%" autocorrect="off" autocapitalize="off" spellcheck="false" />
			<input type="hidden" name="wpsc_merchant_eway_settings" value="1" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_password"><?php echo esc_html_x('API password', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" value="<?php echo esc_attr(get_option('eway_password')); ?>" name="eway_password" id="eway_password"
				class="regular-text" autocorrect="off" autocapitalize="off" spellcheck="false" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_ecrypt_key"><?php echo esc_html_x('Client Side Encryption key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<textarea name="eway_ecrypt_key" id="eway_ecrypt_key" style="width: 100%; height: 6em" autocorrect="off" autocapitalize="off" spellcheck="false"
				><?php echo esc_attr(get_option('eway_ecrypt_key')); ?></textarea>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="ewayCustomerID_id"><?php echo esc_html_x('Customer ID', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" value="<?php echo esc_attr(get_option('ewayCustomerID_id')); ?>" name="ewayCustomerID_id" id="ewayCustomerID_id" />
			<p class="description"><?php esc_html_e('Legacy connections only; please add your API key/password and Client Side Encryption key instead.', 'eway-payment-gateway'); ?></p>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_test_label">
			<?php echo esc_html_x('Sandbox mode', 'settings field', 'eway-payment-gateway'); ?>
		</th>
		<td>
			<input type="radio" value="1" name="eway_test" id="eway_test_1" <?php checked($eway_test, '1'); ?> aria-labelledby="eway_test_label eway_test_label_1" />
			<label for="eway_test_1" id="eway_test_label_1"><?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<input type="radio" value="0" name="eway_test" id="eway_test_0" <?php checked($eway_test, '0'); ?> aria-labelledby="eway_test_label eway_test_label_0" />
			<label for="eway_test_0" id="eway_test_label_0"><?php echo TXT_WPSC_NO; ?></label>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_sandbox_api_key"><?php echo esc_html_x('Sandbox API key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" value="<?php echo esc_attr(get_option('eway_sandbox_api_key')); ?>" name="eway_sandbox_api_key" id="eway_sandbox_api_key"
				style="width: 100%" autocorrect="off" autocapitalize="off" spellcheck="false" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_sandbox_password"><?php echo esc_html_x('Sandbox API password', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" value="<?php echo esc_attr(get_option('eway_sandbox_password')); ?>" name="eway_sandbox_password" id="eway_sandbox_password"
				class="regular-text" autocorrect="off" autocapitalize="off" spellcheck="false" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_sandbox_ecrypt_key"><?php echo esc_html_x('Sandbox Client Side Encryption key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<textarea name="eway_sandbox_ecrypt_key" id="eway_sandbox_ecrypt_key" style="width: 100%; height: 6em" autocorrect="off" autocapitalize="off" spellcheck="false"
				><?php echo esc_attr(get_option('eway_sandbox_ecrypt_key')); ?></textarea>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_stored_label"><?php echo esc_html_x('Stored payments', 'settings field', 'eway-payment-gateway'); ?></th>
		<td>
			<input type="radio" value="1" name="eway_stored" id="eway_stored_1" <?php checked($eway_stored, '1'); ?> aria-labelledby="eway_stored_label eway_stored_label_1" />
			<label for="eway_stored_1" id="eway_stored_label_1"><?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<input type="radio" value="0" name="eway_stored" id="eway_stored_0" <?php checked($eway_stored, '0'); ?> aria-labelledby="eway_stored_label eway_stored_label_0" />
			<label for="eway_stored_0" id="eway_stored_label_0"><?php echo TXT_WPSC_NO; ?></label>
			<p id="wpsc-eway-admin-stored-test" style="color:#c00">
				<?php esc_html_e('NB: Stored Payments uses the Direct Payments sandbox; there is no Stored Payments sandbox.', 'eway-payment-gateway'); ?>
			</p>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_logging_label"><?php echo esc_html_x('Logging', 'settings field', 'eway-payment-gateway'); ?></th>
		<td>
			<input type="radio" value="off" name="eway_logging" id="eway_logging_off" <?php checked($eway_logging, 'off'); ?> aria-labelledby="eway_logging_label eway_logging_label_off" />
			<label for="eway_logging_off" id="eway_logging_label_off"><?php echo esc_html_x('Off', 'logging settings', 'eway-payment-gateway'); ?></label> &nbsp;
			<input type="radio" value="info" name="eway_logging" id="eway_logging_info" <?php checked($eway_logging, 'info'); ?> aria-labelledby="eway_logging_label eway_logging_label_info" />
			<label for="eway_logging_info" id="eway_logging_label_info"><?php echo esc_html_x('All messages', 'logging settings', 'eway-payment-gateway'); ?></label> &nbsp;
			<input type="radio" value="error" name="eway_logging" id="eway_logging_error" <?php checked($eway_logging, 'error'); ?> aria-labelledby="eway_logging_label eway_logging_label_error" />
			<label for="eway_logging_error" id="eway_logging_label_error"><?php echo esc_html_x('Errors only', 'logging settings', 'eway-payment-gateway'); ?></label> &nbsp;
			<p class="description">
				<?php esc_html_e('Enable logging to assist trouble shooting;', 'eway-payment-gateway'); ?>
				<br /><?php esc_html_e('the log file can be found in this folder:', 'eway-payment-gateway'); ?>
				<br /><?php echo esc_html(EwayPaymentsLogging::getLogFolderRelative()); ?>
			</p>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_th_label"><?php echo esc_html_x('Use TH for field labels', 'settings field', 'eway-payment-gateway'); ?></th>
		<td>
			<input type="radio" value="1" name="eway_th" id="eway_th_1" <?php checked($eway_th, '1'); ?> aria-labelledby="eway_th_label eway_th_label_1" />
			<label for="eway_th_1" id="eway_th_label_1"><?php echo TXT_WPSC_YES; ?></label> &nbsp;
			<input type="radio" value="0" name="eway_th" id="eway_th_0" <?php checked($eway_th, '0'); ?> aria-labelledby="eway_th_label eway_th_label_0" />
			<label for="eway_th_0" id="eway_th_label_0"><?php echo TXT_WPSC_NO; ?></label>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_card_msg"><?php echo esc_html_x('Credit card message', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" style="width:100%" value="<?php echo esc_attr(get_option('wpsc_merchant_eway_card_msg')); ?>" name="eway_card_msg" id="eway_card_msg" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_first_name"><?php echo esc_html_x('First name', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[first_name]" id="eway_form_first_name">
				<?php EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_first_name')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_last_name"><?php echo esc_html_x('Last name', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[last_name]" id="eway_form_last_name">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_last_name')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_address"><?php echo esc_html_x('Address', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[address]" id="eway_form_address">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_address')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_city"><?php echo esc_html_x('City', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[city]" id="eway_form_city">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_city')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_state"><?php echo esc_html_x('State', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[state]" id="eway_form_state">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_state')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_post_code"><?php echo esc_html_x('Postal/Zip code', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[post_code]" id="eway_form_post_code">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_post_code')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_country"><?php echo esc_html_x('Country', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[country]" id="eway_form_country">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_country')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_email"><?php echo esc_html_x('Email address', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[email]" id="eway_form_email">
				<?php echo EwayPaymentsWpsc::showCheckoutFormFields(get_option('eway_form_email')); ?>
			</select>
		</td>
	</tr>

