<?php
/*
If you want to customise the checkout form, copy this file into your theme folder and edit it there.
Take care to keep the field names the same, or your checkout form won't charge credit cards!

* $checkoutURL = where to post the checkout form data
* $card_msg = credit card message (e.g. what cards are accepted)
* $optMonths = options for drop-down list of months of the year
* $optYears = options for drop-down list of current year + 15

*/

if (!defined('ABSPATH')) {
	exit;
}
?>

<?php if (!empty($checkout_message)): ?>
<p><?= $checkout_message; ?></p>
<?php endif; ?>

<form action="<?= esc_url($checkoutURL); ?>" method="post" id="awpcp-eway-checkout">

<fieldset>

	<?php if (!empty($card_msg)): ?>
	<p class="awpcp-eway-cardmessage"><?= $card_msg; ?></p>
	<?php endif; ?>

	<p class="form-row form-row-first">
		<label for="eway_card_number"><?php esc_html_e('Credit Card Number', 'eway-payment-gateway'); ?> <span class="required">*</span></label>
		<input type="text" value="" pattern="[0-9]*" name="eway_card_number" id="eway_card_number"
			title="<?php esc_html_e('only digits 0-9 are accepted', 'eway-payment-gateway'); ?>" autocomplete="off" />
	</p>

	<p class="form-row form-row-last">
		<label for="eway_card_name"><?php esc_html_e("Card Holder's Name", 'eway-payment-gateway'); ?> <span class="required">*</span></label>
		<input type="text" value="" name="eway_card_name" id="eway_card_name" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" />
	</p>

	<div class="clear"></div>

	<p class="form-row form-row-first">
		<label for="eway_expiry_month"><?php esc_html_e('Credit Card Expiry', 'eway-payment-gateway'); ?> <span class="required">*</span></label>
		<select name="eway_expiry_month" id="eway_expiry_month" title="<?php esc_html_e('credit card expiry month', 'eway-payment-gateway'); ?>">
			<option value=""><?= esc_html_x('Month', 'credit card field', 'eway-payment-gateway'); ?></option>
			<?= $optMonths; ?>
		</select>
		<select name="eway_expiry_year" title="<?php esc_html_e('credit card expiry year', 'eway-payment-gateway'); ?>">
			<option value=""><?= esc_html_x('Year', 'credit card field', 'eway-payment-gateway'); ?></option>
			<?= $optYears; ?>
		</select>
	</p>

	<p class="form-row form-row-last">
		<label for="eway_cvn"><?= esc_html_e('CVN/CVV', 'eway-payment-gateway'); ?> <span class="required">*</span></label>
		<input type="text" size="4" maxlength="4" value="" pattern="[0-9]*" name="eway_cvn" id="eway_cvn"
			title="<?php esc_html_e('only digits 0-9 are accepted', 'eway-payment-gateway'); ?>" autocomplete="off" />
	</p>

	<div class="clear"></div>

	<p>
		<input type="submit" value="Make payment" />
		<?php if (get_awpcp_option('eway_site_seal_code')): ?>
			<?= wp_kses_post(get_awpcp_option('eway_site_seal_code')); ?>
		<?php else: ?>
		<a href="https://www.eway.com.au/" rel="noopener" target="_blank">
			<img src="<?= esc_url(plugins_url('static/images/eway-small.svg', EWAY_PAYMENTS_PLUGIN_FILE)); ?>" />
		</a>
		<?php endif; ?>
	</p>

</fieldset>

</form>
