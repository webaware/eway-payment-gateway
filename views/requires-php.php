<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-error">
	<p>
		<?php echo eway_payment_gateway_external_link(
				sprintf(esc_html__('eWAY Payment Gateway requires PHP %1$s or higher; your website has PHP %2$s which is {{a}}old, obsolete, and unsupported{{/a}}.', 'eway-payment-gateway'),
					esc_html(EWAY_PAYMENTS_MIN_PHP), esc_html(PHP_VERSION)),
				'https://www.php.net/supported-versions.php'
			); ?>
	</p>
	<p><?php printf(esc_html__('Please upgrade your website hosting. At least PHP %s is recommended.', 'eway-payment-gateway'), '7.2'); ?></p>
</div>
