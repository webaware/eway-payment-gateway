<?php
namespace webaware\eway_payment_gateway\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Facebook\WebDriver\Exception\UnexpectedAlertOpenException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class WooCommerceTest extends TestCase {

	private static $web_driver;

	/**
	 * create a web driver for testing
	 */
	public static function setUpBeforeClass() : void {
		self::$web_driver = webdriver_get_driver();
	}

	/**
	 * close the web driver after tests complete
	 */
	public static function tearDownAfterClass(): void {
		self::$web_driver->close();
		self::$web_driver = null;
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

		$driver = self::$web_driver;

		$driver->get($plugin_test_env['url_woo_shop']);
		$driver->executeScript('jQuery(".add_to_cart_button").first().click()');
		$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.added_to_cart'))
		);

		$driver->get($plugin_test_env['url_woo_checkout']);
		$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('place_order'))
		);

		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_first_name')), 'Test');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_last_name')), 'Only');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_company')), 'Testers, Inc.');
		$driver->executeScript('jQuery("#billing_country").val("AU").trigger("change")');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_address_1')), '123 Example Street');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_city')), 'Sometown');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_postcode')), '2000');
		$driver->executeScript('jQuery("#billing_state").val("NSW").trigger("change")');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_phone')), '0123456789');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_email')), 'test@example.com');

		$driver->executeScript('jQuery("#payment_method_eway_payments").click()');
		$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('payment_method_eway_payments'))
		);
		webdriver_replace_value($driver->findElement(WebDriverBy::id('eway_payments-card-name')), 'Test Only');
		$driver->executeScript('jQuery("#eway_payments-card-number").val("4444333322221111").trigger("change")');
		$driver->executeScript('jQuery("#eway_payments-card-expiry").val("1230").trigger("change")');
		$driver->executeScript('jQuery("#eway_payments-card-cvc").val("123").trigger("change")');

		$driver->executeScript('jQuery("#place_order").click()');

		$driver->wait(30)->until(
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

		$driver = self::$web_driver;

		$driver->get($plugin_test_env['url_woo_shop']);
		$driver->executeScript('jQuery(".add_to_cart_button").first().click()');
		$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.added_to_cart'))
		);

		$driver->get($plugin_test_env['url_woo_checkout']);
		$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('place_order'))
		);

		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_first_name')), 'Test');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_last_name')), 'Only');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_company')), 'Testers, Inc.');
		$driver->executeScript('jQuery("#billing_country").val("AU").trigger("change")');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_address_1')), '123 Example Street');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_city')), 'Sometown');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_postcode')), '2000');
		$driver->executeScript('jQuery("#billing_state").val("NSW").trigger("change")');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_phone')), '0123456789');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_email')), 'test@example.com');

		$driver->executeScript('jQuery("#payment_method_eway_payments").click()');
		$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('payment_method_eway_payments'))
		);
		webdriver_replace_value($driver->findElement(WebDriverBy::id('eway_payments-card-name')), 'Test Only');
		$driver->executeScript('jQuery("#eway_payments-card-number").val("4444333322221112").trigger("change")');
		$driver->executeScript('jQuery("#eway_payments-card-expiry").val("1230").trigger("change")');
		$driver->executeScript('jQuery("#eway_payments-card-cvc").val("123").trigger("change")');

		$driver->executeScript('jQuery("#place_order").click()');

		$driver->wait(30)->until(
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

		$driver = self::$web_driver;

		$driver->get($plugin_test_env['url_woo_shop']);
		$driver->executeScript('jQuery(".add_to_cart_button").first().click()');
		$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.added_to_cart'))
		);

		$driver->get($plugin_test_env['url_woo_checkout']);
		$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('place_order'))
		);

		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_first_name')), 'Test');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_last_name')), 'Only');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_company')), 'Testers, Inc.');
		$driver->executeScript('jQuery("#billing_country").val("AU").trigger("change")');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_address_1')), '123 Example Street');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_city')), 'Sometown');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_postcode')), '2000');
		$driver->executeScript('jQuery("#billing_state").val("NSW").trigger("change")');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_phone')), '0123456789');
		webdriver_replace_value($driver->findElement(WebDriverBy::id('billing_email')), 'test@example.com');

		$driver->executeScript('jQuery("#payment_method_eway_payments").click()');
		$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('payment_method_eway_payments'))
		);
		webdriver_replace_value($driver->findElement(WebDriverBy::id('eway_payments-card-name')), 'Test Only');
		$driver->executeScript('jQuery("#eway_payments-card-number").val("4444333322221111").trigger("change")');
		$driver->executeScript('jQuery("#eway_payments-card-expiry").val("1230").trigger("change")');

		$driver->executeScript('jQuery("#place_order").click()');

		$driver->wait()->until(
			WebDriverExpectedCondition::elementTextContains(
				WebDriverBy::cssSelector('.woocommerce-error li'),
				'Please enter CVN (Card Verification Number)'
			)
		);

		$this->assertTrue(true);
	}

}
