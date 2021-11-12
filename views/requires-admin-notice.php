<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-error">
	<p><?php esc_html_e('Eway Payment Gateway is not fully active.', 'eway-payment-gateway'); ?></p>
	<ul style="list-style:disc;padding-left: 2em">
		<?php foreach ($notices as $notice): ?>
			<li><?php echo $notice; ?></li>
		<?php endforeach; ?>
	</ul>
</div>
