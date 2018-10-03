<?php
namespace webaware\eway_payment_gateway\event_espresso;

use webaware\eway_payment_gateway\FormPost;

if (!defined('ABSPATH')) {
	exit;
}

/**
* extract billing information from Event Espresso
*/
class BillingInfo {

	public $card_name;
	public $card_number;
	public $expiry_month;
	public $expiry_year;
	public $cvn;
	public $first_name;
	public $last_name;
	public $email;
	public $phone;
	public $address;
	public $address2;
	public $city;
	public $state;
	public $zip;
	public $country;

	/**
	* @param array $billing_info
	*/
	public function __construct($billing_info) {
		// collect raw post data, to access CSE encrypted fields
		$postdata = new FormPost();

		foreach (array_keys(get_object_vars($this)) as $key) {
			switch ($key) {

				case 'card_number':
					$this->card_number = $postdata->cleanCardnumber($postdata->getValue('card_number'));
					break;

				case 'cvn':
					$this->cvn = $postdata->getValue('cvn');
					break;

				case 'country':
					$this->country = $this->getPostedCountry();
					break;

				default:
					if (!empty($billing_info[$key])) {
						$this->$key = $billing_info[$key];
					}
					break;

			}
		}
	}

	/**
	* get country code from billing details posted by checkout
	* -- because Event Espresso has converted it to a country name to pass to the checkout ¯\_(ツ)_/¯
	* @return string
	*/
	protected function getPostedCountry() {
		$billing_form = false;

		$iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($_POST), \RecursiveIteratorIterator::SELF_FIRST);
		foreach ($iterator as $key => $value) {
			if ($key === 'billing_form') {
				$billing_form = $value;
				break;
			}
		}

		return isset($billing_form['country']) ? sanitize_text_field(wp_unslash($billing_form['country'])) : '';
	}

}
