<?php
// custom fields for Events Manager admin page

if (!defined('ABSPATH')) {
	exit;
}
?>

<style>
.form-table td em {
	clear:both;
	display:block;
	margin:0.4em;
}
</style>

<table class="form-table">
<tbody>
  <tr valign="top">
	  <th scope="row"><label for="em_eway_booking_feedback"><?php _e('Success Message', 'em-pro') ?></label></th>
	  <td>
		<input type="text" name="em_eway_booking_feedback" id="em_eway_booking_feedback" value="<?php echo esc_attr(get_option('em_eway_booking_feedback')); ?>" class="large-text" />
		<em><?php _e('The message that is shown to a user when a booking is successful and payment has been taken.','em-pro'); ?></em>
	  </td>
  </tr>
  <tr valign="top">
	  <th scope="row"><label for="em_eway_booking_feedback_free"><?php _e('Success Free Message', 'em-pro') ?></label></th>
	  <td>
		<input type="text" name="em_eway_booking_feedback_free" id="em_eway_booking_feedback_free" value="<?php echo esc_attr(get_option('em_eway_booking_feedback_free')); ?>" class="large-text" />
		<em><?php _e('If some cases if you allow a free ticket (e.g. pay at gate) as well as paid tickets, this message will be shown and the user will not be charged.','em-pro'); ?></em>
	  </td>
  </tr>
</tbody>
</table>

<h3><?php echo sprintf(__('%s Options','dbem'),'eWAY')?></h3>

<table class="form-table">
<tbody>

	<tr valign="top">
		<th scope="row"><label for="em_eway_mode"><?php _e('Mode', 'em-pro'); ?></label></th>
		<td>
			<select name="em_eway_mode" id="em_eway_mode">
				<?php $selected = get_option('em_eway_mode'); ?>
				<option value="sandbox" <?php selected($selected, 'sandbox'); ?>><?php _e('Sandbox','emp-pro'); ?></option>
				<option value="live" <?php selected($selected, 'live'); ?>><?php _e('Live','emp-pro'); ?></option>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="em_eway_cust_id">eWAY customer ID</label></th>
		<td><input type="text" name="em_eway_cust_id" id="em_eway_cust_id" value="<?php echo esc_attr(get_option('em_eway_cust_id')); ?>" /></td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="em_eway_card_msg">Credit card message</label></th>
		<td>
			<input type="text" name="em_eway_card_msg" id="em_eway_card_msg" value="<?php echo esc_attr(get_option('em_eway_card_msg')); ?>" class="large-text" />
			<em>Message to show above credit card fields, e.g. &quot;Visa and Mastercard only&quot;</em>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="em_eway_test_force">Force ID 87654321 for sandbox</label></th>
		<td>
			<select name="em_eway_test_force" id="em_eway_test_force">
				<?php $selected = get_option('em_eway_test_force'); ?>
				<option value="1" <?php selected($selected, '1'); ?>>Yes</option>
				<option value="0" <?php selected($selected, '0'); ?>>No</option>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="em_eway_ssl_force">Force SSL for bookings form</label></th>
		<td>
			<select name="em_eway_ssl_force" id="em_eway_ssl_force">
				<?php $selected = get_option('em_eway_ssl_force'); ?>
				<option value="1" <?php selected($selected, '1'); ?>>Yes</option>
				<option value="0" <?php selected($selected, '0'); ?>>No</option>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="em_eway_logging">Logging</label></th>
		<td>
			<select name="em_eway_logging" id="em_eway_logging">
				<?php $selected = get_option('em_eway_logging'); ?>
				<option value="off" <?php selected($selected, 'off'); ?>>Off</option>
				<option value="info" <?php selected($selected, 'info'); ?>>All messages</option>
				<option value="error" <?php selected($selected, 'error'); ?>>Errors only</option>
			</select>
			<em>Enable logging to assist trouble shooting. The log file can be found in:</em>
			<em><?php echo esc_html(substr(EwayPaymentsLogging::getLogFolder(), strlen(ABSPATH))); ?></em>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="em_eway_stored">Stored payments</label></th>
		<td>
			<select name="em_eway_stored" id="em_eway_stored">
				<?php $selected = get_option('em_eway_stored'); ?>
				<option value="1" <?php selected($selected, '1'); ?>>Yes</option>
				<option value="0" <?php selected($selected, '0'); ?>>No</option>
			</select>
			<em>Stored payments records payment details but doesn't bill immediately.</em>
			<em id="em-eway-admin-stored-test" style='color:#c00'>Stored Payments uses the Direct Payments sandbox; there is no Stored Payments sandbox.</em>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="em_eway_beagle">Beagle (free)</label></th>
		<td>
			<select name="em_eway_beagle" id="em_eway_beagle">
				<?php $selected = get_option('em_eway_beagle'); ?>
				<option value="1" <?php selected($selected, '1'); ?>>Yes</option>
				<option value="0" <?php selected($selected, '0'); ?>>No</option>
			</select>
			<em><a href="https://www.eway.com.au/developers/api/beagle-lite" target="_blank">Beagle</a>
			 is a service from eWAY that provides a level of fraud protection for your transactions.
			 It uses information about the IP address of the purchaser to suggest whether there is a risk of fraud.
			 You must configure Beagle rules in your MYeWAY console before enabling Beagle.</em>
			<em>You will also need to add a Country field to your booking form. Beagle works by comparing the country of the address with
			the country where the purchaser is using the Internet; Beagle won't be used when booking without a country selected.</em>
			<em id="em-eway-admin-stored-beagle" style='color:#c00'>Beagle is not available for Stored Payments</em>
		</td>
	</tr>

	<tr><th colspan="2"><?php echo sprintf(__( '%s Options', 'dbem' ),__('Advanced','em-pro')); ?></th></tr>

	<tr valign="top">
	  <th scope="row"><label for="em_eway_manual_approval"><?php _e('Manually approve completed transactions?', 'em-pro') ?></label></th>
	  <td>
		<input type="checkbox" name="em_eway_manual_approval" id="em_eway_manual_approval" value="1" <?php echo (get_option('em_eway_manual_approval')) ? 'checked="checked"':''; ?> />
		<em><?php _e('By default, when someone pays for a booking, it gets automatically approved once the payment is confirmed. If you would like to manually verify and approve bookings, tick this box.','em-pro'); ?></em>
		<em><?php echo sprintf(__('Approvals must also be required for all bookings in your <a href="%s">settings</a> for this to work properly.','em-pro'),EM_ADMIN_URL.'&amp;page=events-manager-options'); ?></em>
	  </td>
	</tr>

</tbody>
</table>

<script>
(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function setVisibility() {
		var	useTest = ($("select[name='em_eway_mode']").val() == "sandbox"),
			useBeagle = ($("select[name='em_eway_beagle']").val() == "1"),
			useStored = ($("select[name='em_eway_stored']").val() == "1");

		function display(element, visible) {
			if (visible)
				element.css({display: "none"}).show(750);
			else
				element.hide();
		}

		display($("#em-eway-admin-stored-test"), (useTest && useStored));
		display($("#em-eway-admin-stored-beagle"), (useBeagle && useStored));
	}

	$("form[name='gatewaysettingsform']").on("change", "select[name='em_eway_mode'],select[name='em_eway_stored'],select[name='em_eway_beagle']", setVisibility);

	setVisibility();

})(jQuery);
</script>
