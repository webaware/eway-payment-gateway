<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* wrapper for form post data
*/
class EwayPaymentsFormPost {

	protected static $postdata = null;

	/**
	* maybe unslash the post data and store for access
	*/
	public function __construct() {
		if (is_null(self::$postdata)) {
			self::$postdata = wp_unslash($_POST);
		}
	}

	/**
	* get field value (trimmed if it's a string), or return null if not found
	* @param string $field_name
	* @return mixed|null
	*/
	public function getValue($field_name) {
		if (!isset(self::$postdata[$field_name])) {
			return null;
		}

		$value = self::$postdata[$field_name];

		return is_string($value) ? trim($value) : $value;
	}

	/**
	* get array field subkey value (trimmed if it's a string), or return null if not found
	* @param string $field_name
	* @param string $subkey
	* @return mixed|null
	*/
	public function getSubkey($field_name, $subkey) {
		if (!isset(self::$postdata[$field_name][$subkey])) {
			return null;
		}

		$value = self::$postdata[$field_name][$subkey];

		return is_string($value) ? trim($value) : $value;
	}

	/**
	* clean up a credit card number value, removing common extraneous characters
	* @param string $value
	* @return string
	*/
	public function cleanCardnumber($value) {
		return strtr($value, array(' ' => '', '-' => ''));
	}

    /**
    * verify credit card details
    * @param array $ map of field names to values, using standardised field names
    * @return array an array of error messages
    */
    public function verifyCardDetails($fields) {
		$errors = array();

		if ($fields['card_number'] === '') {
			$errors[] = __('Please enter credit card number', 'eway-payment-gateway');
		}

		if ($fields['card_name'] === '') {
			$errors[] = __('Please enter card holder name', 'eway-payment-gateway');
		}

		if (empty($fields['expiry_month']) || !preg_match('/^(?:0[1-9]|1[012])$/', $fields['expiry_month'])) {
			$errors[] = __('Please select credit card expiry month', 'eway-payment-gateway');
			$expiryError = true;
		}

		// FIXME: if this code makes it into the 2100's, update this regex!
		$expiryError = false;
		if (empty($fields['expiry_year']) || !preg_match('/^20[0-9]{2}$/', $fields['expiry_year'])) {
			$errors[] = __('Please select credit card expiry year', 'eway-payment-gateway');
			$expiryError = true;
		}

		if (!$expiryError) {
			// check that first day of month after expiry isn't earlier than today
			$expired = mktime(0, 0, 0, 1 + $fields['expiry_month'], 0, $fields['expiry_year']);
			$today = time();
			if ($expired < $today) {
				$errors[] = __('Credit card expiry has passed', 'eway-payment-gateway');
			}
		}

		if ($fields['cvn'] === '') {
			$errors[] = __('Please enter CVN (Card Verification Number)', 'eway-payment-gateway');
		}

		return $errors;
	}

}
