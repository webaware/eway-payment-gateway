<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-error">
	<p><?php esc_html_e('Eway payments will not process without a Rapid API key and password.', 'eway-payment-gateway'); ?></p>
	<p>
		<?= eway_payment_gateway_external_link(
			esc_html__('Please refer to Eway documentation for {{a}}setting up your live Eway API key and password{{/a}}.', 'eway-payment-gateway'),
			'https://go.eway.io/s/article/How-do-I-setup-my-Live-eWAY-API-Key-and-Password'
		) ?>
	</p>
	<p>
		<?= eway_payment_gateway_external_link(
			esc_html__('For testing the gateway, please refer to {{a}}setting up your Eway sandbox API key and password{{/a}}.', 'eway-payment-gateway'),
			'https://go.eway.io/s/article/How-do-I-set-up-my-Sandbox-API-Key-and-password'
		) ?>
	</p>
	<p>
		<?= eway_payment_gateway_external_link(
			esc_html__('It is likely that you will also require a {{a}}Client Side Encryption key{{/a}}.', 'eway-payment-gateway'),
			'https://go.eway.io/s/article/How-do-I-set-up-Client-Side-Encryption'
		) ?>
	</p>
</div>
