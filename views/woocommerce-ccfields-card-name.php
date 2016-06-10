<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<p class="form-row form-row-wide">
	<label for="<?php echo esc_attr($this->id); ?>-card-name">Card Holder's Name <span class="required">*</span></label>
	<input id="<?php echo esc_attr($this->id); ?>-card-name" class="input-text wc-credit-card-form-card-name" type="text" maxlength="50" autocomplete="off" name="<?php echo esc_attr($this->id); ?>-card-name" />
</p>
