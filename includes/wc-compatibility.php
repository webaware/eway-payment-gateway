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


if (!class_exists('WC_Payment_Gateway_CC', false)) {

	/**
	* shim for WC_Payment_Gateway_CC for WooCommerce < 2.6
	*/
	class WC_Payment_Gateway_CC extends WC_Payment_Gateway {

		/**
		 * Outputs fields for entering credit card information.
		 * @since 2.6.0
		 */
		public function form() {
			$this->credit_card_form();
		}

	}

}
