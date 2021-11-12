<?php
// show Eway billing details

if (!defined('ABSPATH')) {
	exit;
}
?>

<blockquote>
	<?php if (!empty($purchlogitem->extrainfo->transactid)): ?>
	<strong><?php esc_html_e('Transaction ID:', 'eway-payment-gateway'); ?></strong> <?= esc_html($purchlogitem->extrainfo->transactid); ?><br/>
	<?php endif; ?>
	<?php if (!empty($purchlogitem->extrainfo->authcode)): ?>
	<strong><?php esc_html_e('Auth Code:', 'eway-payment-gateway'); ?></strong> <?= esc_html($purchlogitem->extrainfo->authcode); ?><br/>
	<?php endif; ?>
</blockquote>

