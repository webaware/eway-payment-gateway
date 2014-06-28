<?php
// compatibility layer for WooCommerce versions

if (!function_exists('wc_add_notice')) {

	/**
	 * Add and store a notice
	 *
	 * @param  string $message The text to display in the notice.
	 * @param  string $notice_type The singular name of the notice type - either error, success or notice. [optional]
	 */
	function wc_add_notice( $message, $notice_type = 'success' ) {
		// call pre-WC2.1 equivalent
		global $woocommerce;

		if ($notice_type == 'error') {
			$woocommerce->add_error($message);
		}
		else {
			$woocommerce->add_message($message);
		}
	}

}

