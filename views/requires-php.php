<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-error">
	<p><?php printf(__('eWAY Payment Gateway requires PHP %1$s or higher; your website has PHP %2$s which is <a rel="noopener" target="_blank" href="%3$s">old, obsolete, and unsupported</a>.', 'eway-payment-gateway'),
			esc_html(EWAY_PAYMENTS_MIN_PHP), esc_html(PHP_VERSION), 'https://secure.php.net/supported-versions.php'); ?></p>
	<p><?php printf(__('Please upgrade your website hosting. At least PHP %s is recommended.', 'eway-payment-gateway'), '7.1'); ?></p>
</div>
