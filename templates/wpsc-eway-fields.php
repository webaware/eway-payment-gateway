<?php
/*
If you want to customise the checkout form, copy this file into your theme folder and edit it there.
Take care to keep the field names the same, or your checkout form won't charge credit cards!

* $th = 'th' or 'td' depending on Settings -> Store -> Payments -> eWAY
* $optMonths = options for drop-down list of months of the year
* $optYears = options for drop-down list of current year + 15

*/
?>
<tr class='wpsc-merch-eway-row'>
	<<?php echo $th; ?>><label>Credit Card Number <span class='asterix'>*</span></label></<?php echo $th; ?>>
	<td>
		<input type='text' value='' name='card_number' id='eway_card_number' />
	</td>
</tr>

<tr class='wpsc-merch-eway-row'>
	<<?php echo $th; ?>><label>Card Holder's Name <span class='asterix'>*</span></label></<?php echo $th; ?>>
	<td>
		<input type='text' value='' name='card_name' id='eway_card_name' />
	</td>
</tr>

<tr class='wpsc-merch-eway-row'>
	<<?php echo $th; ?>><label>Credit Card Expiry <span class='asterix'>*</span></label></<?php echo $th; ?>>
	<td style='white-space: nowrap'>
	<select class='wpsc_ccBox' name='expiry_month' style='width: 4em'>
		<?php echo $optMonths; ?>
	</select><span>/</span><select class='wpsc_ccBox' name='expiry_year' style='width: 5em'>
		<?php echo $optYears; ?>
	</select>
	</td>
</tr>

<tr class='wpsc-merch-eway-row'>
	<<?php echo $th; ?>><label id='eway_cvn'>CVN <span class='asterix'>*</span></label></<?php echo $th; ?>>
	<td>
		<input type='text' size='4' maxlength='4' value='' name='cvn' id='eway_cvn' />
	</td>
</tr>
