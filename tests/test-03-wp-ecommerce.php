<?php
namespace webaware\eway_payment_gateway\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Facebook\WebDriver\Exception\UnexpectedAlertOpenException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class WPeCommerceTest extends TestCase {

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

		$this->assertArrayHasKey('url_wpsc_home', $plugin_test_env);
		$this->assertArrayHasKey('url_wpsc_shop', $plugin_test_env);
		$this->assertArrayHasKey('url_wpsc_checkout', $plugin_test_env);
	}

	/**
	 * add a product to the cart and try to checkout
	 * @depends testEnvironment
	 */
	public function testPurchase() : void {
		global $plugin_test_env;

		$this->web->driver->get($plugin_test_env['url_wpsc_shop']);
		$this->web->driver->findElement(WebDriverBy::cssSelector('.wpsc_buy_button'))->click();

		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.gocheckout'))
		);

		$this->web->driver->get($plugin_test_env['url_wpsc_checkout']);
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('checkout_page_container'))
		);

		$this->web->sendKeys('[data-wpsc-meta-key="billingemail"]:not([type="hidden"])', 'test@example.com');
		$this->web->sendKeys('[data-wpsc-meta-key="billingfirstname"]:not([type="hidden"])', 'Test');
		$this->web->sendKeys('[data-wpsc-meta-key="billinglastname"]:not([type="hidden"])', 'Only');
		$this->web->sendKeys('[data-wpsc-meta-key="billingaddress"]:not([type="hidden"])', '123 Example Street');
		$this->web->sendKeys('[data-wpsc-meta-key="billingcity"]:not([type="hidden"])', 'Sometown');
		$this->web->sendKeys('[data-wpsc-meta-key="billingstate"]:not([type="hidden"])', 'New South Wales');
		$this->web->setFieldValue('select[data-wpsc-meta-key="billingcountry"]', 'AU');
		$this->web->sendKeys('[data-wpsc-meta-key="billingpostcode"]:not([type="hidden"])', '2000');
		$this->web->sendKeys('[data-wpsc-meta-key="billingphone"]:not([type="hidden"])', '0123456789');

		$shipping_same = $this->web->driver->findElement(WebDriverBy::id('shippingSameBilling'));
		if ($shipping_same->getDomProperty('checked')) {
			$shipping_same->click();
		}

		$this->web->sendKeys('[data-wpsc-meta-key="shippingfirstname"]:not([type="hidden"])', 'Test');
		$this->web->sendKeys('[data-wpsc-meta-key="shippinglastname"]:not([type="hidden"])', 'Only');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingaddress"]:not([type="hidden"])', '123 Example Street');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingcity"]:not([type="hidden"])', 'Sometown');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingstate"]:not([type="hidden"])', 'New South Wales');
		$this->web->setFieldValue('select[data-wpsc-meta-key="shippingcountry"]', 'AU');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingpostcode"]:not([type="hidden"])', '2000');

		$this->web->driver->findElement(WebDriverBy::cssSelector('input.custom_gateway[value="wpsc_merchant_eway"]'))->click();

		$this->web->sendKeys('#eway_card_name', 'Test Only');
		$this->web->sendKeys('#eway_card_number', '4444333322221111');
		$this->web->setFieldValue('#eway_expiry_month', '12');
		$this->web->setFieldValue('#eway_expiry_year', date('Y') + 9);
		$this->web->sendKeys('#eway_cvn', '123');

		$this->web->driver->findElement(WebDriverBy::cssSelector('.make_purchase'))->click();

		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.wpsc-transaction-results-wrap'))
		);

		$this->assertTrue(true);
	}

	/**
	 * add a product to the cart and try to checkout, failing due to bad card number
	 * @depends testEnvironment
	 */
	public function testFailCardnumber() : void {
		global $plugin_test_env;

		// expect a popup alert about bad card number
		$this->expectException(UnexpectedAlertOpenException::class);

		$this->web->driver->get($plugin_test_env['url_wpsc_shop']);
		$this->web->driver->findElement(WebDriverBy::cssSelector('.wpsc_buy_button'))->click();

		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.gocheckout'))
		);

		$this->web->driver->get($plugin_test_env['url_wpsc_checkout']);
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('checkout_page_container'))
		);

		$this->web->sendKeys('[data-wpsc-meta-key="billingemail"]:not([type="hidden"])', 'test@example.com');
		$this->web->sendKeys('[data-wpsc-meta-key="billingfirstname"]:not([type="hidden"])', 'Test');
		$this->web->sendKeys('[data-wpsc-meta-key="billinglastname"]:not([type="hidden"])', 'Only');
		$this->web->sendKeys('[data-wpsc-meta-key="billingaddress"]:not([type="hidden"])', '123 Example Street');
		$this->web->sendKeys('[data-wpsc-meta-key="billingcity"]:not([type="hidden"])', 'Sometown');
		$this->web->sendKeys('[data-wpsc-meta-key="billingstate"]:not([type="hidden"])', 'New South Wales');
		$this->web->setFieldValue('select[data-wpsc-meta-key="billingcountry"]', 'AU');
		$this->web->sendKeys('[data-wpsc-meta-key="billingpostcode"]:not([type="hidden"])', '2000');
		$this->web->sendKeys('[data-wpsc-meta-key="billingphone"]:not([type="hidden"])', '0123456789');

		$shipping_same = $this->web->driver->findElement(WebDriverBy::id('shippingSameBilling'));
		if ($shipping_same->getDomProperty('checked')) {
			$shipping_same->click();
		}

		$this->web->sendKeys('[data-wpsc-meta-key="shippingfirstname"]:not([type="hidden"])', 'Test');
		$this->web->sendKeys('[data-wpsc-meta-key="shippinglastname"]:not([type="hidden"])', 'Only');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingaddress"]:not([type="hidden"])', '123 Example Street');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingcity"]:not([type="hidden"])', 'Sometown');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingstate"]:not([type="hidden"])', 'New South Wales');
		$this->web->setFieldValue('select[data-wpsc-meta-key="shippingcountry"]', 'AU');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingpostcode"]:not([type="hidden"])', '2000');

		$this->web->driver->findElement(WebDriverBy::cssSelector('input.custom_gateway[value="wpsc_merchant_eway"]'))->click();

		$this->web->sendKeys('#eway_card_name', 'Test Only');
		$this->web->sendKeys('#eway_card_number', '4444333322221112');
		$this->web->setFieldValue('#eway_expiry_month', '12');
		$this->web->setFieldValue('#eway_expiry_year', date('Y') + 9);
		$this->web->sendKeys('#eway_cvn', '123');

		$this->web->driver->findElement(WebDriverBy::cssSelector('.make_purchase'))->click();

		// should never get here, because of an UnexpectedAlertOpenException, but need to wait...
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.wpsc-transaction-results-wrap'))
		);

		$this->assertTrue(true);
	}

	/**
	 * add a product to the cart and try to checkout, failing due to missing CVN
	 * @depends testEnvironment
	 */
	public function testFailCVN() : void {
		global $plugin_test_env;

		$this->web->driver->get($plugin_test_env['url_wpsc_shop']);
		$this->web->driver->findElement(WebDriverBy::cssSelector('.wpsc_buy_button'))->click();

		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.gocheckout'))
		);

		$this->web->driver->get($plugin_test_env['url_wpsc_checkout']);
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('checkout_page_container'))
		);

		$this->web->sendKeys('[data-wpsc-meta-key="billingemail"]:not([type="hidden"])', 'test@example.com');
		$this->web->sendKeys('[data-wpsc-meta-key="billingfirstname"]:not([type="hidden"])', 'Test');
		$this->web->sendKeys('[data-wpsc-meta-key="billinglastname"]:not([type="hidden"])', 'Only');
		$this->web->sendKeys('[data-wpsc-meta-key="billingaddress"]:not([type="hidden"])', '123 Example Street');
		$this->web->sendKeys('[data-wpsc-meta-key="billingcity"]:not([type="hidden"])', 'Sometown');
		$this->web->sendKeys('[data-wpsc-meta-key="billingstate"]:not([type="hidden"])', 'New South Wales');
		$this->web->setFieldValue('select[data-wpsc-meta-key="billingcountry"]', 'AU');
		$this->web->sendKeys('[data-wpsc-meta-key="billingpostcode"]:not([type="hidden"])', '2000');
		$this->web->sendKeys('[data-wpsc-meta-key="billingphone"]:not([type="hidden"])', '0123456789');

		$shipping_same = $this->web->driver->findElement(WebDriverBy::id('shippingSameBilling'));
		if ($shipping_same->getDomProperty('checked')) {
			$shipping_same->click();
		}

		$this->web->sendKeys('[data-wpsc-meta-key="shippingfirstname"]:not([type="hidden"])', 'Test');
		$this->web->sendKeys('[data-wpsc-meta-key="shippinglastname"]:not([type="hidden"])', 'Only');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingaddress"]:not([type="hidden"])', '123 Example Street');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingcity"]:not([type="hidden"])', 'Sometown');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingstate"]:not([type="hidden"])', 'New South Wales');
		$this->web->setFieldValue('select[data-wpsc-meta-key="shippingcountry"]', 'AU');
		$this->web->sendKeys('[data-wpsc-meta-key="shippingpostcode"]:not([type="hidden"])', '2000');

		$this->web->driver->findElement(WebDriverBy::cssSelector('input.custom_gateway[value="wpsc_merchant_eway"]'))->click();

		$this->web->sendKeys('#eway_card_name', 'Test Only');
		$this->web->sendKeys('#eway_card_number', '4444333322221111');
		$this->web->setFieldValue('#eway_expiry_month', '12');
		$this->web->setFieldValue('#eway_expiry_year', date('Y') + 9);

		$this->web->driver->findElement(WebDriverBy::cssSelector('.make_purchase'))->click();

		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::elementTextContains(
				WebDriverBy::cssSelector('.validation-error'),
				'Please enter CVN (Card Verification Number)'
			)
		);

		$this->assertTrue(true);
	}

}
