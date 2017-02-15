<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* extend legacy WooCommerce order class to mimic WC 2.7+ order class
*/
class EwayPaymentsWooOrder extends WC_Order {

	public function get_id() {
		return $this->id;
	}

	public function get_total() {
		return $this->order_total;
	}

	public function get_currency() {
		return $this->order_currency;
	}

	public function get_billing_first_name() {
		return $this->billing_first_name;
	}

	public function get_billing_last_name() {
		return $this->billing_last_name;
	}

	public function get_billing_company() {
		return $this->billing_company;
	}

	public function get_billing_email() {
		return $this->billing_email;
	}

	public function get_billing_phone() {
		return $this->billing_phone;
	}

	public function get_billing_address_1() {
		return $this->billing_address_1;
	}

	public function get_billing_address_2() {
		return $this->billing_address_2;
	}

	public function get_billing_city() {
		return $this->billing_city;
	}

	public function get_billing_state() {
		return $this->billing_state;
	}

	public function get_billing_postcode() {
		return $this->billing_postcode;
	}

	public function get_billing_country() {
		return $this->billing_country;
	}

	public function get_customer_note() {
		return $this->customer_note;
	}

	public function get_shipping_first_name() {
		return $this->shipping_first_name;
	}

	public function get_shipping_last_name() {
		return $this->shipping_last_name;
	}

	public function get_shipping_address_1() {
		return $this->shipping_address_1;
	}

	public function get_shipping_address_2() {
		return $this->shipping_address_2;
	}

	public function get_shipping_city() {
		return $this->shipping_city;
	}

	public function get_shipping_state() {
		return $this->shipping_state;
	}

	public function get_shipping_country() {
		return $this->shipping_country;
	}

	public function get_shipping_postcode() {
		return $this->shipping_postcode;
	}

}
