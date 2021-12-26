<?php
namespace webaware\eway_payment_gateway\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class EventEspressoTest extends TestCase {

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

		$this->assertArrayHasKey('url_event_espresso', $plugin_test_env);
	}

	/**
	 * add a booking for the first event found
	 * @depends testEnvironment
	 */
	public function testBooking() : void {
		global $plugin_test_env;

		$this->web->driver->get($plugin_test_env['url_event_espresso']);
		$this->web->driver->findElement(WebDriverBy::cssSelector('.ticket-selector-submit-btn.view-details-btn'))->click();
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.single-espresso_events'))
		);

		$this->web->selectByValue('select.ticket-selector-tbl-qty-slct', '1');
		$this->web->driver->findElement(WebDriverBy::cssSelector('.ticket-selector-submit-btn'))->click();
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.ee-reg-form-attendee-dv'))
		);

		$this->web->sendKeys('input.ee-reg-qstn-fname', 'Test');
		$this->web->sendKeys('input.ee-reg-qstn-lname', 'Only');
		$this->web->sendKeys('input.ee-reg-qstn-email', 'test@example.com');
		$this->web->driver->findElement(WebDriverBy::cssSelector('.spco-next-step-btn'))->click();
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.spco-payment-method'))
		);

		$this->web->driver->findElement(WebDriverBy::id('event-espresso-eway-form'))->click();

		$this->web->sendKeys('#event-espresso-eway-form-address', '123 Example Street');
		$this->web->sendKeys('#event-espresso-eway-form-city', 'Sometown');
		$this->web->selectByText('#event-espresso-eway-form-state', 'New South Wales');
		$this->web->selectByValue('#event-espresso-eway-form-country', 'AU');
		$this->web->sendKeys('#event-espresso-eway-form-zip', '2000');
		$this->web->sendKeys('#event-espresso-eway-form-phone', '0123456789');

		$this->web->sendKeys('#event-espresso-eway-form-card-number', '4444333322221111');
		$this->web->sendKeys('#event-espresso-eway-form-card-name', 'Test Only');
		$this->web->selectByValue('#event-espresso-eway-form-expiry-month', '12');
		$this->web->selectByValue('#event-espresso-eway-form-expiry-year', date('Y') + 9);
		$this->web->sendKeys('#event-espresso-eway-form-cvn', '123');

		$this->web->driver->findElement(WebDriverBy::id('spco-go-to-step-finalize_registration-submit'))->click();

		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('espresso-thank-you-page-overview-dv'))
		);

		$this->assertTrue(true);
	}

}
