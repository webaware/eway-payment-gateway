<?php
/*
If you want to customise the checkout form, copy this file into your theme folder and edit it there.
Take care to keep the field names the same, or your checkout form won't charge credit cards!

* $optMonths = options for drop-down list of months of the year
* $optYears = options for drop-down list of current year + 15

*/
?>

<fieldset>

	<p class="form-row form-row-first">
		<label>Credit Card Number <span class='required'>*</span></label>
		<input type='text' value='' name='eway_card_number' id='eway_card_number' />
	</p>

	<p class="form-row form-row-last">
		<label>Card Holder's Name <span class='required'>*</span></label>
		<input type='text' value='' name='eway_card_name' id='eway_card_name' />
	</p>

	<div class="clear"></div>

	<p class="form-row form-row-first">
		<label>Credit Card Expiry <span class='required'>*</span></label>
		<select name='eway_expiry_month' class="woocommerce-select woocommerce-cc-month">
			<option value="">Month</option>
			<?php echo $optMonths; ?>
		</select>
		<select name='eway_expiry_year' class="woocommerce-select woocommerce-cc-year">
			<option value="">Year</option>
			<?php echo $optYears; ?>
		</select>
	</p>

	<p class="form-row form-row-last">
		<label id='eway_cvn'>CVN <span class='required'>*</span></label>
		<input type='text' size='4' maxlength='4' value='' name='eway_cvn' id='eway_cvn' />
	</p>

	<div class="clear"></div>

</fieldset>