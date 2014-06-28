<?php
// custom fields for Events Manager admin page
?>

<table class="form-table">
<tbody>
  <tr valign="top">
	  <th scope="row"><?php _e('Success Message', 'em-pro') ?></th>
	  <td>
		<input type="text" name="booking_feedback" value="<?php echo esc_attr(get_option('em_'. EM_EWAY_GATEWAY . "_booking_feedback")); ?>" style='width: 40em;' /><br />
		<em><?php _e('The message that is shown to a user when a booking is successful and payment has been taken.','em-pro'); ?></em>
	  </td>
  </tr>
  <tr valign="top">
	  <th scope="row"><?php _e('Success Free Message', 'em-pro') ?></th>
	  <td>
		<input type="text" name="booking_feedback_free" value="<?php echo esc_attr(get_option('em_'. EM_EWAY_GATEWAY . "_booking_feedback_free" )); ?>" style='width: 40em;' /><br />
		<em><?php _e('If some cases if you allow a free ticket (e.g. pay at gate) as well as paid tickets, this message will be shown and the user will not be charged.','em-pro'); ?></em>
	  </td>
  </tr>
</tbody>
</table>

<h3><?php echo sprintf(__('%s Options','dbem'),'eWAY')?></h3>

<table class="form-table">
<tbody>

	<tr valign="top">
		<th scope="row"><?php _e('Mode', 'em-pro'); ?></th>
		<td>
			<select name="eway_mode">
				<?php $selected = get_option('em_'.EM_EWAY_GATEWAY.'_mode'); ?>
				<option value="sandbox" <?php selected($selected, 'sandbox'); ?>><?php _e('Sandbox','emp-pro'); ?></option>
				<option value="live" <?php selected($selected, 'live'); ?>><?php _e('Live','emp-pro'); ?></option>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">eWAY customer ID</th>
		<td><input type="text" name="eway_cust_id" value="<?php echo esc_attr(get_option( 'em_'. EM_EWAY_GATEWAY . "_cust_id", "")); ?>" /></td>
	</tr>

	<tr valign="top">
		<th scope="row">Credit card message</th>
		<td>
			<input type="text" name="eway_card_msg" value="<?php echo esc_attr(get_option( 'em_'. EM_EWAY_GATEWAY . "_card_msg", "")); ?>" style='width: 40em;' />
			<em><br />Message to show above credit card fields, e.g. &quot;Visa and Mastercard only&quot;</em>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">Force ID 87654321 for sandbox</th>
		<td>
			<select name="eway_test_force">
				<?php $selected = get_option('em_'.EM_EWAY_GATEWAY.'_test_force'); ?>
				<option value="1" <?php selected($selected, '1'); ?>>Yes</option>
				<option value="0" <?php selected($selected, '0'); ?>>No</option>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">Force SSL for bookings form</th>
		<td>
			<select name="eway_ssl_force">
				<?php $selected = get_option('em_'.EM_EWAY_GATEWAY.'_ssl_force'); ?>
				<option value="1" <?php selected($selected, '1'); ?>>Yes</option>
				<option value="0" <?php selected($selected, '0'); ?>>No</option>
			</select>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">Stored payments</th>
		<td>
			<select name="eway_stored">
				<?php $selected = get_option('em_'.EM_EWAY_GATEWAY.'_stored'); ?>
				<option value="1" <?php selected($selected, '1'); ?>>Yes</option>
				<option value="0" <?php selected($selected, '0'); ?>>No</option>
			</select><br />
			<em><a href='http://www.eway.com.au/how-it-works/payment-products#stored-payments' target="_blank">Stored payments</a>
			 records payment details but doesn't bill immediately.</em>
			<em id="em-eway-admin-stored-test" style='color:#c00'><br />Stored Payments uses the Direct Payments sandbox; there is no Stored Payments sandbox.</em>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">Beagle (free)</th>
		<td>
			<select name="eway_beagle">
				<?php $selected = get_option('em_'.EM_EWAY_GATEWAY.'_beagle'); ?>
				<option value="1" <?php selected($selected, '1'); ?>>Yes</option>
				<option value="0" <?php selected($selected, '0'); ?>>No</option>
			</select><br />
			<em><a href="http://www.eway.com.au/developers/resources/beagle-(free)-rules" target="_blank">Beagle</a>
			 is a service from eWAY that provides a level of fraud protection for your transactions.
			 It uses information about the IP address of the purchaser to suggest whether there is a risk of fraud.
			 You must configure <a href="http://www.eway.com.au/developers/resources/beagle-(free)-rules" target="_blank">Beagle rules</a>
			 in your MYeWAY console before enabling Beagle.</em>
			<em><br />You will also need to add a Country field to your booking form. Beagle works by comparing the country of the address with
			the country where the purchaser is using the Internet; Beagle won't be used when booking without a country selected.</em>
			<em id="em-eway-admin-stored-beagle" style='color:#c00'><br />Beagle is not available for Stored Payments</em>
		</td>
	</tr>

	<tr><td colspan="2"><strong><?php echo sprintf(__( '%s Options', 'dbem' ),__('Advanced','em-pro')); ?></strong></td></tr>

	<tr valign="top">
	  <th scope="row"><?php _e('Manually approve completed transactions?', 'em-pro') ?></th>
	  <td>
		<input type="checkbox" name="manual_approval" value="1" <?php echo (get_option('em_'. EM_EWAY_GATEWAY . "_manual_approval" )) ? 'checked="checked"':''; ?> /><br />
		<em><?php _e('By default, when someone pays for a booking, it gets automatically approved once the payment is confirmed. If you would like to manually verify and approve bookings, tick this box.','em-pro'); ?></em><br />
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
		var	useTest = ($("select[name='eway_mode']").val() == "sandbox"),
			useBeagle = ($("select[name='eway_beagle']").val() == "1"),
			useStored = ($("select[name='eway_stored']").val() == "1");

		function display(element, visible) {
			if (visible)
				element.css({display: "none"}).show(750);
			else
				element.hide();
		}

		display($("#em-eway-admin-stored-test"), (useTest && useStored));
		display($("#em-eway-admin-stored-beagle"), (useBeagle && useStored));
	}

	$("form[name='gatewaysettingsform']").on("change", "select[name='eway_mode'],select[name='eway_stored'],select[name='eway_beagle']", setVisibility);

	setVisibility();

})(jQuery);
</script>
