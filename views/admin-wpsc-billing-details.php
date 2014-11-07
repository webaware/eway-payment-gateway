<?php
// show eWAY billing details
?>

<blockquote>
	<?php if (!empty($purchlogitem->extrainfo->transactid)): ?>
	<strong>Transaction ID:</strong> <?php echo esc_html($purchlogitem->extrainfo->transactid); ?><br/>
	<?php endif; ?>
	<?php if (!empty($purchlogitem->extrainfo->authcode)): ?>
	<strong>Auth Code:</strong> <?php echo esc_html($purchlogitem->extrainfo->authcode); ?><br/>
	<?php endif; ?>
</blockquote>

