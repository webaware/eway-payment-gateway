<?php
// WooCommerce admin settings page

if (!defined('ABSPATH')) {
	exit;
}
?>

<img style="float:right" src="<?= esc_url(plugins_url('images/eway-siteseal.png', EWAY_PAYMENTS_PLUGIN_FILE)); ?>" />
<h3><?= esc_html($this->admin_page_heading); ?></h3>
<p><?= esc_html($this->admin_page_description); ?></p>
<table class="form-table">
<?php $this->generate_settings_html(); ?>
</table>
