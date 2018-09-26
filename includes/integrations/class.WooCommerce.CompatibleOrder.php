<?php
namespace webaware\eway_payment_gateway\woocommerce;

if (!defined('ABSPATH')) {
	exit;
}

/**
* extend legacy WooCommerce order class to mimic WC 2.7+ order class
*/
class CompatibleOrder extends \WC_Order {

	public function get_id() {
		return $this->id;
	}

	public function get_total($context = 'view') {
		return $this->order_total;
	}

	public function get_currency($context = 'view') {
		return $this->order_currency;
	}

	public function get_billing_first_name($context = 'view') {
		return $this->billing_first_name;
	}

	public function get_billing_last_name($context = 'view') {
		return $this->billing_last_name;
	}

	public function get_billing_company($context = 'view') {
		return $this->billing_company;
	}

	public function get_billing_email($context = 'view') {
		return $this->billing_email;
	}

	public function get_billing_phone($context = 'view') {
		return $this->billing_phone;
	}

	public function get_billing_address_1($context = 'view') {
		return $this->billing_address_1;
	}

	public function get_billing_address_2($context = 'view') {
		return $this->billing_address_2;
	}

	public function get_billing_city($context = 'view') {
		return $this->billing_city;
	}

	public function get_billing_state($context = 'view') {
		return $this->billing_state;
	}

	public function get_billing_postcode($context = 'view') {
		return $this->billing_postcode;
	}

	public function get_billing_country($context = 'view') {
		return $this->billing_country;
	}

	public function get_customer_note($context = 'view') {
		return $this->customer_note;
	}

	public function get_shipping_first_name($context = 'view') {
		return $this->shipping_first_name;
	}

	public function get_shipping_last_name($context = 'view') {
		return $this->shipping_last_name;
	}

	public function get_shipping_address_1($context = 'view') {
		return $this->shipping_address_1;
	}

	public function get_shipping_address_2($context = 'view') {
		return $this->shipping_address_2;
	}

	public function get_shipping_city($context = 'view') {
		return $this->shipping_city;
	}

	public function get_shipping_state($context = 'view') {
		return $this->shipping_state;
	}

	public function get_shipping_country($context = 'view') {
		return $this->shipping_country;
	}

	public function get_shipping_postcode($context = 'view') {
		return $this->shipping_postcode;
	}

}
