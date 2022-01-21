<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-warning is-dismissible">
	<p><?php esc_html_e('The next version of Eway Payment Gateway will require changes to your website.', 'eway-payment-gateway'); ?></p>

	<ul style="list-style:disc;padding-left: 2em">
		<?php foreach ($notices as $notice): ?>
			<li><?php echo $notice; ?></li>
		<?php endforeach; ?>
	</ul>

	<p><?php esc_html_e("If you can't make these changes yet, please don't upgrade to version 5.0 or above.", 'eway-payment-gateway'); ?></p>

	<button type="button" class="eway-payment-gateway-notice-dismiss" style="margin-bottom: 0.5em"
		data-dismiss="<?= esc_attr($dismiss) ?>" data-nonce="<?= esc_attr(wp_create_nonce($dismiss)) ?>">
		<?php esc_html_e('Dismiss', 'eway-payment-gateway'); ?>
	</button>
</div>
