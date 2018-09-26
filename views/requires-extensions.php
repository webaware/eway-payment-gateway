<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="error">
	<p><?php esc_html_e('eWAY Payment Gateway requires these missing PHP extensions. Please contact your website host to have these extensions installed.', 'eway-payment-gateway'); ?></p>
	<ul style="padding-left: 2em">
		<?php foreach ($missing as $ext): ?>
		<li style="list-style-type:disc"><?= esc_html($ext); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
