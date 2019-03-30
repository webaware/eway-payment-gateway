<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-error">
	<?php /* translators: %1$s: minimum required version number, %2$s: installed version number */ ?>
	<p><?= sprintf(esc_html__('eWAY Payment Gateway requires WooCommerce version %1$s or higher; your website has WooCommerce version %2$s', 'eway-payment-gateway'),
		esc_html(MIN_VERSION_WOOCOMMERCE), esc_html(WC()->version)); ?></p>
</div>
