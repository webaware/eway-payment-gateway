<?php
use webaware\eway_payment_gateway\Logging;

// custom fields for Events Manager admin page

if (!defined('ABSPATH')) {
	exit;
}
?>

<table class="form-table">
<tbody>

	<tr valign="top">
		<th scope="row">
			<label for="em_eway_mode"><?= translate('Mode', 'em-pro'); ?></label>
		</th>
		<td>
			<select name="em_eway_mode" id="em_eway_mode">
				<?php $selected = get_option('em_eway_mode'); ?>
				<option value="sandbox" <?php selected($selected, 'sandbox'); ?>><?= translate('Sandbox', 'emp-pro'); ?></option>
				<option value="live" <?php selected($selected, 'live'); ?>><?= translate('Live', 'emp-pro'); ?></option>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="em_eway_api_key"><?= esc_html_x('API key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" name="em_eway_api_key" id="em_eway_api_key" value="<?= esc_attr(get_option('em_eway_api_key')); ?>"
				class="large-text" autocorrect="off" autocapitalize="off" spellcheck="false" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="em_eway_password"><?= esc_html_x('API password', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="password" name="em_eway_password" id="em_eway_password" value="<?= esc_attr(get_option('em_eway_password')); ?>"
				autocorrect="new-password" autocapitalize="off" spellcheck="false" />
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="em_eway_ecrypt_key"><?= esc_html_x('Client Side Encryption key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<textarea name="em_eway_ecrypt_key" id="em_eway_ecrypt_key" class="large-text"
				autocorrect="off" autocapitalize="off" spellcheck="false"><?= esc_html(get_option('em_eway_ecrypt_key')); ?></textarea>
		</td>
	</tr>

	<tr valign="top" class="em_eway_sandbox_row">
		<th scope="row">
			<label for="em_eway_sandbox_api_key"><?= esc_html_x('Sandbox API key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" name="em_eway_sandbox_api_key" id="em_eway_sandbox_api_key" value="<?= esc_attr(get_option('em_eway_sandbox_api_key')); ?>"
				class="large-text" autocorrect="off" autocapitalize="off" spellcheck="false" />
		</td>
	</tr>

	<tr valign="top" class="em_eway_sandbox_row">
		<th scope="row">
			<label for="em_eway_sandbox_password"><?= esc_html_x('Sandbox API password', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="password" name="em_eway_sandbox_password" id="em_eway_sandbox_password" value="<?= esc_attr(get_option('em_eway_sandbox_password')); ?>"
				autocorrect="new-password" autocapitalize="off" spellcheck="false" />
		</td>
	</tr>

	<tr valign="top" class="em_eway_sandbox_row">
		<th scope="row">
			<label for="em_eway_sandbox_ecrypt_key"><?= esc_html_x('Sandbox Client Side Encryption key', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<textarea name="em_eway_sandbox_ecrypt_key" id="em_eway_sandbox_ecrypt_key" class="large-text"
				autocorrect="off" autocapitalize="off" spellcheck="false"><?= esc_html(get_option('em_eway_sandbox_ecrypt_key')); ?></textarea>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="em_eway_card_msg"><?= esc_html_x('Credit card message', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" name="em_eway_card_msg" id="em_eway_card_msg" value="<?= esc_attr(get_option('em_eway_card_msg')); ?>" class="large-text" />
			<em>Message to show above credit card fields, e.g. &quot;Visa and Mastercard only&quot;</em>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="em_eway_ssl_force"><?= esc_html_x('Force SSL for bookings form', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="em_eway_ssl_force" id="em_eway_ssl_force">
				<?php $selected = get_option('em_eway_ssl_force'); ?>
				<option value="1" <?php selected($selected, '1'); ?>><?= translate('Yes', 'events-manager'); ?></option>
				<option value="0" <?php selected($selected, '0'); ?>><?= translate('No', 'events-manager'); ?></option>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="em_eway_logging"><?= esc_html_x('Logging', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="em_eway_logging" id="em_eway_logging">
				<?php $selected = get_option('em_eway_logging'); ?>
				<option value="off" <?php selected($selected, 'off'); ?>><?= esc_html_x('Off', 'logging settings', 'eway-payment-gateway'); ?></option>
				<option value="info" <?php selected($selected, 'info'); ?>><?= esc_html_x('All messages', 'logging settings', 'eway-payment-gateway'); ?></option>
				<option value="error" <?php selected($selected, 'error'); ?>><?= esc_html_x('Errors only', 'logging settings', 'eway-payment-gateway'); ?></option>
			</select>
			<em><?php esc_html_e('Enable logging to assist trouble shooting', 'eway-payment-gateway'); ?>
				<br /><?php esc_html_e('the log file can be found in this folder:', 'eway-payment-gateway'); ?>
				<br /><?= esc_html(Logging::getLogFolderRelative()); ?>
			</em>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="em_eway_stored"><?= esc_html_x('Payment Method', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<select name="em_eway_stored" id="em_eway_stored">
				<?php $selected = get_option('em_eway_stored'); ?>
				<option value="0"<?php selected($selected, '0'); ?>><?= esc_html_x('Capture', 'payment method', 'eway-payment-gateway'); ?></option>
				<option value="1"<?php selected($selected, '1'); ?>><?= esc_html_x('Authorize', 'payment method', 'eway-payment-gateway'); ?></option>
			</select>
			<em><?php esc_html_e("Capture processes the payment immediately. Authorize holds the amount on the customer's card for processing later.", 'eway-payment-gateway'); ?></em>
		</td>
	</tr>

</tbody>
</table>
