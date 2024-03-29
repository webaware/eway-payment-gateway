<?php
use webaware\eway_payment_gateway\Logging;
use webaware\eway_payment_gateway\MethodWPeCommerce;

// custom fields for WP eCommerce admin page

if (!defined('ABSPATH')) {
	exit;
}
?>

	<tr valign="top">
		<th scope="row">
			<label for="eway_api_key"><?= esc_html_x('API key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" value="<?= esc_attr(get_option('eway_api_key')); ?>" name="eway_api_key" id="eway_api_key"
				style="width: 100%" autocorrect="off" autocapitalize="off" spellcheck="false" />
			<input type="hidden" name="wpsc_merchant_eway_settings" value="1" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_password"><?= esc_html_x('API password', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="password" value="<?= esc_attr(get_option('eway_password')); ?>" name="eway_password" id="eway_password"
				class="regular-text" autocorrect="new-password" autocapitalize="off" spellcheck="false" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_ecrypt_key"><?= esc_html_x('Client Side Encryption key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<textarea name="eway_ecrypt_key" id="eway_ecrypt_key" style="width: 100%; height: 6em" autocorrect="off" autocapitalize="off" spellcheck="false"
				><?= esc_attr(get_option('eway_ecrypt_key')); ?></textarea>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_test_label">
			<?= esc_html_x('Sandbox mode', 'settings field', 'eway-payment-gateway'); ?>
		</th>
		<td>
			<input type="radio" value="1" name="eway_test" id="eway_test_1" <?php checked($eway_test, '1'); ?> aria-labelledby="eway_test_label eway_test_label_1" />
			<label for="eway_test_1" id="eway_test_label_1"><?= TXT_WPSC_YES; ?></label> &nbsp;
			<input type="radio" value="0" name="eway_test" id="eway_test_0" <?php checked($eway_test, '0'); ?> aria-labelledby="eway_test_label eway_test_label_0" />
			<label for="eway_test_0" id="eway_test_label_0"><?= TXT_WPSC_NO; ?></label>
		</td>
	</tr>

	<tr valign="top" class="eway_sandbox_field_row">
		<th scope="row">
			<label for="eway_sandbox_api_key"><?= esc_html_x('Sandbox API key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" value="<?= esc_attr(get_option('eway_sandbox_api_key')); ?>" name="eway_sandbox_api_key" id="eway_sandbox_api_key"
				style="width: 100%" autocorrect="off" autocapitalize="off" spellcheck="false" />
		</td>
	</tr>

	<tr valign="top" class="eway_sandbox_field_row">
		<th scope="row">
			<label for="eway_sandbox_password"><?= esc_html_x('Sandbox API password', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="password" value="<?= esc_attr(get_option('eway_sandbox_password')); ?>" name="eway_sandbox_password" id="eway_sandbox_password"
				class="regular-text" autocorrect="new-password" autocapitalize="off" spellcheck="false" />
		</td>
	</tr>

	<tr valign="top" class="eway_sandbox_field_row">
		<th scope="row">
			<label for="eway_sandbox_ecrypt_key"><?= esc_html_x('Sandbox Client Side Encryption key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<textarea name="eway_sandbox_ecrypt_key" id="eway_sandbox_ecrypt_key" style="width: 100%; height: 6em" autocorrect="off" autocapitalize="off" spellcheck="false"
				><?= esc_attr(get_option('eway_sandbox_ecrypt_key')); ?></textarea>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_stored_label"><?= esc_html_x('Payment Method', 'settings field', 'eway-payment-gateway'); ?></th>
		<td>
			<input type="radio" value="0" name="eway_stored" id="eway_stored_0" <?php checked($eway_stored, '0'); ?> aria-labelledby="eway_stored_label eway_stored_label_0" />
			<label for="eway_stored_0" id="eway_stored_label_0"><?= esc_html_x('Capture', 'payment method', 'eway-payment-gateway'); ?></label> &nbsp;
			<input type="radio" value="1" name="eway_stored" id="eway_stored_1" <?php checked($eway_stored, '1'); ?> aria-labelledby="eway_stored_label eway_stored_label_1" />
			<label for="eway_stored_1" id="eway_stored_label_1"><?= esc_html_x('Authorize', 'payment method', 'eway-payment-gateway'); ?></label>
			<p class="description">
				<?php esc_html_e("Capture processes the payment immediately. Authorize holds the amount on the customer's card for processing later.", 'eway-payment-gateway'); ?>
			</p>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_logging_label"><?= esc_html_x('Logging', 'settings field', 'eway-payment-gateway'); ?></th>
		<td>
			<input type="radio" value="off" name="eway_logging" id="eway_logging_off" <?php checked($eway_logging, 'off'); ?> aria-labelledby="eway_logging_label eway_logging_label_off" />
			<label for="eway_logging_off" id="eway_logging_label_off"><?= esc_html_x('Off', 'logging settings', 'eway-payment-gateway'); ?></label> &nbsp;
			<input type="radio" value="info" name="eway_logging" id="eway_logging_info" <?php checked($eway_logging, 'info'); ?> aria-labelledby="eway_logging_label eway_logging_label_info" />
			<label for="eway_logging_info" id="eway_logging_label_info"><?= esc_html_x('All messages', 'logging settings', 'eway-payment-gateway'); ?></label> &nbsp;
			<input type="radio" value="error" name="eway_logging" id="eway_logging_error" <?php checked($eway_logging, 'error'); ?> aria-labelledby="eway_logging_label eway_logging_label_error" />
			<label for="eway_logging_error" id="eway_logging_label_error"><?= esc_html_x('Errors only', 'logging settings', 'eway-payment-gateway'); ?></label> &nbsp;
			<p class="description">
				<?php esc_html_e('Enable logging to assist trouble shooting', 'eway-payment-gateway'); ?>
				<br /><?php esc_html_e('the log file can be found in this folder:', 'eway-payment-gateway'); ?>
				<br /><?= esc_html(Logging::getLogFolderRelative()); ?>
			</p>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" id="eway_th_label"><?= esc_html_x('Use TH for field labels', 'settings field', 'eway-payment-gateway'); ?></th>
		<td>
			<input type="radio" value="1" name="eway_th" id="eway_th_1" <?php checked($eway_th, '1'); ?> aria-labelledby="eway_th_label eway_th_label_1" />
			<label for="eway_th_1" id="eway_th_label_1"><?= TXT_WPSC_YES; ?></label> &nbsp;
			<input type="radio" value="0" name="eway_th" id="eway_th_0" <?php checked($eway_th, '0'); ?> aria-labelledby="eway_th_label eway_th_label_0" />
			<label for="eway_th_0" id="eway_th_label_0"><?= TXT_WPSC_NO; ?></label>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_card_msg"><?= esc_html_x('Credit card message', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" style="width:100%" value="<?= esc_attr(get_option('wpsc_merchant_eway_card_msg')); ?>" name="eway_card_msg" id="eway_card_msg" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_first_name"><?= esc_html_x('First name', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[first_name]" id="eway_form_first_name">
				<?php MethodWPeCommerce::showCheckoutFormFields(get_option('eway_form_first_name')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_last_name"><?= esc_html_x('Last name', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[last_name]" id="eway_form_last_name">
				<?php MethodWPeCommerce::showCheckoutFormFields(get_option('eway_form_last_name')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_address"><?= esc_html_x('Address', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[address]" id="eway_form_address">
				<?php MethodWPeCommerce::showCheckoutFormFields(get_option('eway_form_address')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_city"><?= esc_html_x('City', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[city]" id="eway_form_city">
				<?php MethodWPeCommerce::showCheckoutFormFields(get_option('eway_form_city')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_state"><?= esc_html_x('State', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[state]" id="eway_form_state">
				<?php MethodWPeCommerce::showCheckoutFormFields(get_option('eway_form_state')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_post_code"><?= esc_html_x('Postal/Zip code', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[post_code]" id="eway_form_post_code">
				<?php MethodWPeCommerce::showCheckoutFormFields(get_option('eway_form_post_code')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_country"><?= esc_html_x('Country', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[country]" id="eway_form_country">
				<?php MethodWPeCommerce::showCheckoutFormFields(get_option('eway_form_country')); ?>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="eway_form_email"><?= esc_html_x('Email address', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="eway_form[email]" id="eway_form_email">
				<?php MethodWPeCommerce::showCheckoutFormFields(get_option('eway_form_email')); ?>
			</select>
		</td>
	</tr>

