<?php
/*
If you want to customise the checkout form, copy this file into your theme folder and edit it there.
Take care to keep the field names the same, or your checkout form won't charge credit cards!

* $card_msg = credit card message (e.g. what cards are accepted)
* $card_num = credit card number
* $card_name = card holder's name
* $card_code = CVN / CVVN
* $optMonths = options for drop-down list of months of the year
* $optYears = options for drop-down list of current year + 15

*/

if (!defined('ABSPATH')) {
	exit;
}
?>

<?php if (!empty($card_msg)): ?>
<p class="em-bookings-form-gateway-cardmessage"><?php echo $card_msg; ?></p>
<?php endif; ?>

<p class="em-bookings-form-gateway-cardno">
	<label for="eway_card_num"><?php esc_html_e('Credit Card Number', 'eway-payment-gateway'); ?></label>
	<input type="text" size="15" name="x_card_num" id="eway_card_num" value="<?php echo $card_num; ?>" class="input" pattern="[0-9]*"
		title="<?php esc_html_e('only digits 0-9 are accepted', 'eway-payment-gateway'); ?>" autocomplete="off" />
</p>

<p class="em-bookings-form-gateway-cardname">
	<label for="eway_card_name"><?php esc_html_e("Card Holder's Name", 'eway-payment-gateway'); ?></label>
	<input type="text" size="15" name="x_card_name" id="eway_card_name" value="<?php echo $card_name; ?>" class="input" autocomplete="off" />
</p>

<p class="em-bookings-form-gateway-expiry">
	<label for="eway_exp_date_month"><?php esc_html_e('Credit Card Expiry', 'eway-payment-gateway'); ?></label>
	<select name="x_exp_date_month" id="eway_exp_date_month" title="<?php esc_html_e('credit card expiry month', 'eway-payment-gateway'); ?>">
		<?php echo $optMonths; ?>
	</select> /
	<select name="x_exp_date_year" title="<?php esc_html_e('credit card expiry year', 'eway-payment-gateway'); ?>">
		<?php echo $optYears; ?>
	</select>
</p>

<p class="em-bookings-form-ccv">
	<label for="eway_card_code"><?php echo esc_html_e('CVN/CVV', 'eway-payment-gateway'); ?></label>
	<input type="text" size="4" name="x_card_code" id="eway_card_code" value="<?php echo $card_code; ?>" class="input" maxlength="4" pattern="[0-9]*"
		title="<?php esc_html_e('only digits 0-9 are accepted', 'eway-payment-gateway'); ?>" autocomplete="off" />
</p>
