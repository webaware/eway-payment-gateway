<?php
// WooCommerce admin settings page

if (!defined('ABSPATH')) {
	exit;
}
?>

<h3><?= esc_html($this->admin_page_heading); ?></h3>
<p><?= esc_html($this->admin_page_description); ?></p>
<table class="form-table">
<?php $this->generate_settings_html(); ?>
</table>
