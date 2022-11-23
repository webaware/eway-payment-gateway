<?php
namespace webaware\eway_payment_gateway\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * @group awpcp
 */
class AWPCPTest extends TestCase {

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

		$this->assertArrayHasKey('url_awpcp', $plugin_test_env);
	}

	/**
	 * add an advertisement listing
	 * @depends testEnvironment
	 */
	public function testListing() : void {
		global $plugin_test_env;

		$this->web->driver->get($plugin_test_env['url_awpcp']);
		$this->web->driver->findElement(WebDriverBy::cssSelector('.awpcp-classifieds-menu--post-listing-menu-item a'))->click();
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.awpcp-form-steps'))
		);

		$this->web->selectByText('select.awpcp-category-dropdown', 'General');
		$this->web->driver->findElement(WebDriverBy::cssSelector('.awpcp-payment-terms-list input[type="radio"]'))->click();
		$this->web->driver->findElement(WebDriverBy::cssSelector('.form-submit input.button'))->click();
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('ad-title'))
		);

		$this->web->setFieldValue('#ad-title', 'All-purpose widget');
		$this->web->setFieldValue('#websiteurl', 'https://example.com/');
		$this->web->setFieldValue('#ad-contact-name', 'Test Only');
		$this->web->setFieldValue('#ad-contact-email', 'test@example.com');
		$this->web->setFieldValue('#ad-contact-phone', '0123456789');
		$this->web->setFieldValue('#ad-item-price', '100.00');
		$this->web->setFieldValue('#ad-details', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Varietates autem iniurasque fortunae facile veteres philosophorum praeceptis instituta vita superabat. Expressa vero in iis aetatibus, quae iam confirmatae sunt.');
		$this->web->checkboxTickByIndex('#terms-of-service', 0);
		$this->web->driver->findElement(WebDriverBy::cssSelector('.awpcp-submit-listing-button'))->click();
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.awpcp-payment-methods-list'))
		);

		$this->web->radioTickByValue('.awpcp-payment-methods-list-payment-method input[name="payment_method"]', 'eway');
		$this->web->driver->findElement(WebDriverBy::id('submit'))->click();
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('awpcp-eway-checkout'))
		);

		$this->web->sendKeys('#eway_card_number', '4444333322221111');
		$this->web->sendKeys('#eway_card_name', 'Test Only');
		$this->web->selectByValue('#eway_expiry_month', '12');
		$this->web->selectByValue('#eway_expiry_year', date('Y') + 9);
		$this->web->sendKeys('#eway_cvn', '123');

		$this->web->driver->findElement(WebDriverBy::cssSelector('#awpcp-eway-checkout input[type="submit"]'))->click();
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('awpcp-payment-completed-form'))
		);

		$this->web->driver->findElement(WebDriverBy::cssSelector('.awpcp-form-submit input[type="submit"]'))->click();
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.awpcp-updated'))
		);

		$this->assertTrue(true);
	}

}
