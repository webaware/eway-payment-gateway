<?php
namespace webaware\eway_payment_gateway;

// custom fields for Events Manager admin page

if (!defined('ABSPATH')) {
	exit;
}
?>

<table class="form-table">
<tbody>
  <tr valign="top">
	  <th scope="row"><label for="em_eway_booking_feedback"><?= translate('Success Message', 'em-pro'); ?></label></th>
	  <td>
		<input type="text" name="em_eway_booking_feedback" id="em_eway_booking_feedback" value="<?= esc_attr(get_option('em_eway_booking_feedback')); ?>" class="large-text" />
		<em><?= translate('The message that is shown to a user when a booking is successful and payment has been taken.','em-pro'); ?></em>
	  </td>
  </tr>
  <tr valign="top">
	  <th scope="row"><label for="em_eway_booking_feedback_free"><?= translate('Success Free Message', 'em-pro'); ?></label></th>
	  <td>
		<input type="text" name="em_eway_booking_feedback_free" id="em_eway_booking_feedback_free" value="<?= esc_attr(get_option('em_eway_booking_feedback_free')); ?>" class="large-text" />
		<em><?= translate('If some cases if you allow a free ticket (e.g. pay at gate) as well as paid tickets, this message will be shown and the user will not be charged.','em-pro'); ?></em>
	  </td>
  </tr>
</tbody>
</table>

<h3><?= sprintf(translate('%s Options', 'events-manager'), 'eWAY'); ?></h3>

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
			<input type="text" name="em_eway_password" id="em_eway_password" value="<?= esc_attr(get_option('em_eway_password')); ?>"
				autocorrect="off" autocapitalize="off" spellcheck="false" />
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

	<tr valign="top">
		<th scope="row">
			<label for="em_eway_cust_id"><?= esc_html_x('Customer ID', 'settings field', 'eway-payment-gateway'); ?></label>
		</th>
		<td>
			<input type="text" name="em_eway_cust_id" id="em_eway_cust_id" value="<?= esc_attr(get_option('em_eway_cust_id')); ?>" />
			<em><?php esc_html_e('Legacy connections only; please add your API key/password and Client Side Encryption key instead.', 'eway-payment-gateway'); ?></em>
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
			<input type="text" name="em_eway_sandbox_password" id="em_eway_sandbox_password" value="<?= esc_attr(get_option('em_eway_sandbox_password')); ?>"
				autocorrect="off" autocapitalize="off" spellcheck="false" />
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

	<tr><th colspan="2"><?= sprintf(translate('%s Options', 'events-manager'), translate('Advanced', 'em-pro')); ?></th></tr>

	<tr valign="top">
	  <th scope="row"><label for="em_eway_manual_approval"><?= translate('Manually approve completed transactions?', 'em-pro') ?></label></th>
	  <td>
		<input type="checkbox" name="em_eway_manual_approval" id="em_eway_manual_approval" value="1" <?= (get_option('em_eway_manual_approval')) ? 'checked="checked"':''; ?> />
		<em><?= translate('By default, when someone pays for a booking, it gets automatically approved once the payment is confirmed. If you would like to manually verify and approve bookings, tick this box.','em-pro'); ?></em>
		<em><?= sprintf(translate('Approvals must also be required for all bookings in your <a href="%s">settings</a> for this to work properly.','em-pro'),EM_ADMIN_URL.'&amp;page=events-manager-options'); ?></em>
	  </td>
	</tr>

</tbody>
</table>
