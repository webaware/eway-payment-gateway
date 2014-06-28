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
?>

<?php if (!empty($card_msg)): ?>
<p class="em-bookings-form-gateway-cardmessage"><?php echo $card_msg; ?></p>
<?php endif; ?>

<p class="em-bookings-form-gateway-cardno">
  <label><?php  _e('Credit Card Number','em-pro'); ?></label>
  <input type="text" size="15" name="x_card_num" value="<?php echo $card_num; ?>" class="input" pattern="[0-9]*"
	title="only digits 0-9 are accepted" />
</p>

<p class="em-bookings-form-gateway-cardname">
  <label>Name of Card Holder</label>
  <input type="text" size="15" name="x_card_name" value="<?php echo $card_name; ?>" class="input" />
</p>

<p class="em-bookings-form-gateway-expiry">
  <label><?php  _e('Expiry Date','em-pro'); ?></label>
  <select name="x_exp_date_month" >
	<?php echo $optMonths; ?>
  </select> /
  <select name="x_exp_date_year" >
	<?php echo $optYears; ?>
  </select>
</p>

<p class="em-bookings-form-ccv">
  <label>CVN/CVV</label>
  <input type="text" size="4" name="x_card_code" value="<?php echo $card_code; ?>" class="input" maxlength="4" pattern="[0-9]*"
	title="only digits 0-9 are accepted" />
</p>
