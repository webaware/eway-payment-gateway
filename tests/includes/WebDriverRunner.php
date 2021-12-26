<?php
namespace webaware\eway_payment_gateway\Tests;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

/**
 * helpers for test classes that run a webdriver
 */
class WebDriverRunner {

	public ?RemoteWebDriver $driver;

	/**
	 * create handle to a browser driver
	 */
	public function __construct() {
		$host = 'http://localhost:4444/';
		$capabilities = DesiredCapabilities::chrome();
		$this->driver = RemoteWebDriver::create($host, $capabilities);
	}

	/**
	 * close browser window and driver
	 */
	public function close() : void {
		$this->driver->close();
		$this->driver = null;
	}

	/**
	 * set a field field value, clearing any previous value it might have
	 * @param string $selector
	 * @param string $value
	 */
	public function sendKeys(string $selector, string $value) : void {
		$element = $this->driver->findElement(WebDriverBy::cssSelector($selector));
		$element->clear();
		$element->sendKeys($value);
	}

	/**
	 * set a field value and trigger a change event to register the value change
	 * @param string $selector
	 * @param string $value
	 */
	public function setFieldValue(string $selector, string $value) : void {
		$script = sprintf('var f = document.querySelector(%s); f.value = %s; f.dispatchEvent(new Event("change"));', json_encode($selector), json_encode($value));
		$this->driver->executeScript($script);
	}

	/**
	 * set a select element value by value
	 * @param string $selector
	 * @param string $value
	 */
	public function selectByValue(string $selector, string $value) : void {
		$select = new WebDriverSelect($this->driver->findElement(WebDriverBy::cssSelector($selector)));
		$select->selectByValue($value);
	}

	/**
	 * set a select element value by the text label of an option
	 * @param string $selector
	 * @param string $text
	 */
	public function selectByText(string $selector, string $text) : void {
		$select = new WebDriverSelect($this->driver->findElement(WebDriverBy::cssSelector($selector)));
		$select->selectByVisibleText($text);
	}

}
