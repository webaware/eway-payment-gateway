<?php
/*
If you want to customise the checkout form, copy this file into your theme folder and edit it there.
Take care to keep the field names the same, or your checkout form won't charge credit cards!

* $optMonths = options for drop-down list of months of the year
* $optYears = options for drop-down list of current year + 15
* $settings = settings for eWAY payment gateway

*/
?>

<fieldset>

	<?php if (!empty($settings['eway_card_msg'])): ?>
	<p class="eway-credit-card-message"><?php echo $settings['eway_card_msg']; ?></p>
	<?php endif; ?>

	<p class="form-row form-row-first">
		<label>Credit Card Number <span class="required">*</span></label>
		<input type="text" value="" pattern="[0-9]*" name="eway_card_number" id="eway_card_number"
			title="only digits 0-9 are accepted" />
	</p>

	<p class="form-row form-row-last">
		<label>Card Holder's Name <span class="required">*</span></label>
		<input type="text" value="" name="eway_card_name" id="eway_card_name" />
	</p>

	<div class="clear"></div>

	<p class="form-row form-row-first">
		<label>Credit Card Expiry <span class="required">*</span></label>
		<select name="eway_expiry_month" class="woocommerce-select woocommerce-cc-month">
			<option value="">Month</option>
			<?php echo $optMonths; ?>
		</select>
		<select name="eway_expiry_year" class="woocommerce-select woocommerce-cc-year">
			<option value="">Year</option>
			<?php echo $optYears; ?>
		</select>
	</p>

	<p class="form-row form-row-last">
		<label id="eway_cvn">CVN/CVV <span class="required">*</span></label>
		<input type="text" size="4" maxlength="4" value="" pattern="[0-9]*" name="eway_cvn" id="eway_cvn"
			title="only digits 0-9 are accepted" />
	</p>

	<div class="clear"></div>

	<?php if (!empty($settings['eway_site_seal']) && !empty($settings['eway_site_seal_code']) && $settings['eway_site_seal'] == 'yes'):
		echo $settings['eway_site_seal_code'];
	endif; ?>

</fieldset>
