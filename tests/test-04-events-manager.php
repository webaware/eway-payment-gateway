<?php
namespace webaware\eway_payment_gateway\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * @group events-manager
 */
class EventsManagerTest extends TestCase {

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

		$this->assertArrayHasKey('url_em_events', $plugin_test_env);
	}

	/**
	 * add a booking for the first event found
	 * @depends testEnvironment
	 */
	public function testBooking() : void {
		global $plugin_test_env;

		$this->web->driver->get($plugin_test_env['url_em_events']);
		// need to focus on element before clicking
		$this->web->driver->executeScript('var e = document.querySelector(".events-table tbody a");e.focus();e.click()');
		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.em-booking-form-details'))
		);

		$user_login = uniqid('test_');
		$this->web->sendKeys('#attendee_name', 'Test Only');
		$this->web->sendKeys('#user_name', $user_login);
		$this->web->sendKeys('#user_email', "{$user_login}@example.com");
		$this->web->sendKeys('#dbem_address', '123 Example Street');
		$this->web->sendKeys('#dbem_city', 'Sometown');
		$this->web->sendKeys('#dbem_state', 'NSW');
		$this->web->sendKeys('#dbem_zip', '2000');
		$this->web->selectByValue('select[name="dbem_country"]', 'AU');
		$this->web->sendKeys('#dbem_phone', '0123456789');
		$this->web->sendKeys('#dbem_fax', '9876543210');
		$this->web->sendKeys('#booking_comment', 'Automated testing: ' . __FUNCTION__);

		$this->web->driver->executeScript('var e = document.querySelector(\'input[name="data_privacy_consent"]\'); e.checked = true;');

		$this->web->sendKeys('#eway_card_num', '4444333322221111');
		$this->web->sendKeys('#eway_card_name', 'Test Only');
		$this->web->selectByValue('#eway_exp_date_month', '12');
		$this->web->selectByValue('#eway_exp_date_year', date('Y') + 9);
		$this->web->sendKeys('#eway_card_code', '123');

		// need to focus on element before clicking
		$this->web->driver->executeScript('var e = document.querySelector(".em-booking-submit");e.focus();e.click()');

		$this->web->driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.em-booking-message-success'))
		);

		$this->assertTrue(true);
	}

}
