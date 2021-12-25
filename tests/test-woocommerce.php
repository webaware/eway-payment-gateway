<?php
namespace webaware\eway_payment_gateway\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Facebook\WebDriver\Exception\UnexpectedAlertOpenException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class WooCommerceTest extends TestCase {

	public WebDriverRunner $web;

	private static $web_runner;

	/**
	 * create a web driver for testing
	 */
	public static function setUpBeforeClass() : void {
		self::$web_runner = new WebDriverRunner();
	}

	/**
	 * close the web driver after tests complete
	 */
	public static function tearDownAfterClass() : void {
		self::$web_runner->close();
	}

	/**
	 * make the web driver runner available to a test instance
	 */
	protected function setUp() : void {
		parent::setUp();

		$this->web = self::$web_runner;
	}

	/**
	 * ensure that environment has been specified
	 */
	public function testEnvironment() : void {
		global $plugin_test_env;

		$this->assertArrayHasKey('url_woo_shop', $plugin_test_env);
		$this->assertArrayHasKey('url_woo_checkout', $plugin_test_env);
	}

	/**
	 * add a product to the cart and try to checkout
	 * @depends testEnvironment
	 */
	public function testPurchase() : void {
		global $plugin_test_env;

		$this->web->driver->get($plugin_test_env['url_woo_shop']);
		$this->web->driver->executeScript('jQuery(".add_to_cart_button").first().click()');
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.added_to_cart'))
		);

		$this->web->driver->get($plugin_test_env['url_woo_checkout']);
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('place_order'))
		);

		$this->web->sendKeys('#billing_first_name', 'Test');
		$this->web->sendKeys('#billing_last_name', 'Only');
		$this->web->sendKeys('#billing_company', 'Testers, Inc.');
		$this->web->setFieldValue('#billing_country', 'AU');
		$this->web->sendKeys('#billing_address_1', '123 Example Street');
		$this->web->sendKeys('#billing_city', 'Sometown');
		$this->web->sendKeys('#billing_postcode', '2000');
		$this->web->setFieldValue('#billing_state', 'NSW');
		$this->web->sendKeys('#billing_phone', '0123456789');
		$this->web->sendKeys('#billing_email', 'test@example.com');

		$this->web->driver->executeScript('jQuery("#payment_method_eway_payments").click()');
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('payment_method_eway_payments'))
		);
		$this->web->sendKeys('#eway_payments-card-name', 'Test Only');
		$this->web->setFieldValue('#eway_payments-card-number', '4444333322221111');
		$this->web->setFieldValue('#eway_payments-card-expiry', '1230');
		$this->web->setFieldValue('#eway_payments-card-cvc', '123');

		$this->web->driver->executeScript('jQuery("#place_order").click()');

		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.woocommerce-order-received'))
		);

		$this->assertTrue(true);
	}

	/**
	 * add a product to the cart and try to checkout, failing due to bad card number
	 * @depends testEnvironment
	 */
	public function testFailCardnumber() : void {
		global $plugin_test_env;

		$this->expectException(UnexpectedAlertOpenException::class);

		$this->web->driver->get($plugin_test_env['url_woo_shop']);
		$this->web->driver->executeScript('jQuery(".add_to_cart_button").first().click()');
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.added_to_cart'))
		);

		$this->web->driver->get($plugin_test_env['url_woo_checkout']);
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('place_order'))
		);

		$this->web->sendKeys('#billing_first_name', 'Test');
		$this->web->sendKeys('#billing_last_name', 'Only');
		$this->web->sendKeys('#billing_company', 'Testers, Inc.');
		$this->web->setFieldValue('#billing_country', 'AU');
		$this->web->sendKeys('#billing_address_1', '123 Example Street');
		$this->web->sendKeys('#billing_city', 'Sometown');
		$this->web->sendKeys('#billing_postcode', '2000');
		$this->web->setFieldValue('#billing_state', 'NSW');
		$this->web->sendKeys('#billing_phone', '0123456789');
		$this->web->sendKeys('#billing_email', 'test@example.com');

		$this->web->driver->executeScript('jQuery("#payment_method_eway_payments").click()');
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('payment_method_eway_payments'))
		);
		$this->web->sendKeys('#eway_payments-card-name', 'Test Only');
		$this->web->setFieldValue('#eway_payments-card-number', '4444333322221112');
		$this->web->setFieldValue('#eway_payments-card-expiry', '1230');
		$this->web->setFieldValue('#eway_payments-card-cvc', '123');

		$this->web->driver->executeScript('jQuery("#place_order").click()');

		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.woocommerce-order-received'))
		);

		$this->assertTrue(true);
	}

	/**
	 * add a product to the cart and try to checkout, failing due to missing CVN
	 * @depends testEnvironment
	 */
	public function testFailCVN() : void {
		global $plugin_test_env;

		$this->web->driver->get($plugin_test_env['url_woo_shop']);
		$this->web->driver->executeScript('jQuery(".add_to_cart_button").first().click()');
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.added_to_cart'))
		);

		$this->web->driver->get($plugin_test_env['url_woo_checkout']);
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('place_order'))
		);

		$this->web->sendKeys('#billing_first_name', 'Test');
		$this->web->sendKeys('#billing_last_name', 'Only');
		$this->web->sendKeys('#billing_company', 'Testers, Inc.');
		$this->web->setFieldValue('#billing_country', 'AU');
		$this->web->sendKeys('#billing_address_1', '123 Example Street');
		$this->web->sendKeys('#billing_city', 'Sometown');
		$this->web->sendKeys('#billing_postcode', '2000');
		$this->web->setFieldValue('#billing_state', 'NSW');
		$this->web->sendKeys('#billing_phone', '0123456789');
		$this->web->sendKeys('#billing_email', 'test@example.com');

		$this->web->driver->executeScript('jQuery("#payment_method_eway_payments").click()');
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('payment_method_eway_payments'))
		);
		$this->web->sendKeys('#eway_payments-card-name', 'Test Only');
		$this->web->setFieldValue('#eway_payments-card-number', '4444333322221111');
		$this->web->setFieldValue('#eway_payments-card-expiry', '1230');

		$this->web->driver->executeScript('jQuery("#place_order").click()');

		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::elementTextContains(
				WebDriverBy::cssSelector('.woocommerce-error li'),
				'Please enter CVN (Card Verification Number)'
			)
		);

		$this->assertTrue(true);
	}

}
