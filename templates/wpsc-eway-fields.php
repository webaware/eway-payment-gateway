<?php
/*
If you want to customise the checkout form, copy this file into your theme folder and edit it there.
Take care to keep the field names the same, or your checkout form won't charge credit cards!

* $th = 'th' or 'td' depending on Settings -> Store -> Payments -> eWAY
* $optMonths = options for drop-down list of months of the year
* $optYears = options for drop-down list of current year + 15

*/

if (!defined('ABSPATH')) {
	exit;
}
?>

<?php if (!empty($card_msg)): ?>
<tr class="wpsc-merch-eway-row">
	<<?php echo $th; ?> colspan="2" class="wpsc-merch-eway-message"><label><?php echo $card_msg; ?></label></<?php echo $th; ?>>
</tr>
<?php endif; ?>

<tr class="wpsc-merch-eway-row">
	<<?php echo $th; ?> scope="row"><label for="eway_card_number"><?php esc_html_e('Credit Card Number', 'eway-payment-gateway'); ?> <span class="asterix">*</span></label></<?php echo $th; ?>>
	<td>
		<input type="text" value="" pattern="[0-9]*" name="card_number" id="eway_card_number"
			title="<?php esc_html_e('only digits 0-9 are accepted', 'eway-payment-gateway'); ?>" autocomplete="off" />
	</td>
</tr>

<tr class="wpsc-merch-eway-row">
	<<?php echo $th; ?> scope="row"><label for="eway_card_name"><?php esc_html_e("Card Holder's Name", 'eway-payment-gateway'); ?> <span class="asterix">*</span></label></<?php echo $th; ?>>
	<td>
		<input type="text" value="" name="card_name" id="eway_card_name" autocomplete="off" />
	</td>
</tr>

<tr class="wpsc-merch-eway-row">
	<<?php echo $th; ?> scope="row"><label for="eway_expiry_month"><?php esc_html_e('Credit Card Expiry', 'eway-payment-gateway'); ?> <span class="asterix">*</span></label></<?php echo $th; ?>>
	<td style="white-space: nowrap">
		<select class="wpsc_ccBox" name="expiry_month" id="eway_expiry_month" style="width: 4em" title="<?php esc_html_e('credit card expiry month', 'eway-payment-gateway'); ?>">
		<?php echo $optMonths; ?>
		</select><span>/</span><select class="wpsc_ccBox" name="expiry_year" id="eway_expiry_year" style="width: 5em" title="<?php esc_html_e('credit card expiry year', 'eway-payment-gateway'); ?>">
		<?php echo $optYears; ?>
		</select>
	</td>
</tr>

<tr class="wpsc-merch-eway-row">
	<<?php echo $th; ?> scope="row"><label for="eway_cvn"><?php echo esc_html_e('CVN/CVV', 'eway-payment-gateway'); ?> <span class="asterix">*</span></label></<?php echo $th; ?>>
	<td>
		<input type="text" size="4" maxlength="4" value="" pattern="[0-9]*" name="cvn" id="eway_cvn"
			title="<?php esc_html_e('only digits 0-9 are accepted', 'eway-payment-gateway'); ?>" autocomplete="off" />
	</td>
</tr>
