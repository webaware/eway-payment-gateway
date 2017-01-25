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

}
