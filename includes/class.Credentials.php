<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Eway Rapid API credentials and keys
 */
final class Credentials {

	public string $api_key;
	public string $password;
	public string $ecrypt_key;

	/**
	 * construct from strings
	 */
	public function __construct(?string $api_key, ?string $password, ?string $ecrypt_key) {
		$this->api_key		= $api_key ?? '';
		$this->password		= $password ?? '';
		$this->ecrypt_key	= $ecrypt_key ?? '';
	}

	/**
	 * check that at bare minimum, an API key and password are set
	 */
	public function isMissingCredentials() : bool {
		return empty($this->api_key) || empty($this->password);
	}

	/**
	 * check whether a Client Side Encryption key is present
	 */
	public function hasCSEKey() : bool {
		return !empty($this->ecrypt_key);
	}

}
