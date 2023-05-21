<?php
namespace webaware\eway_payment_gateway\Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverCheckboxes;
use Facebook\WebDriver\WebDriverRadios;
use Facebook\WebDriver\WebDriverSelect;

/**
 * helpers for test classes that run a webdriver
 */
class WebDriverRunner {

	public ?RemoteWebDriver $driver;

	/**
	 * create handle to a browser driver
	 * NB: run Chrome in private browsing (incognito) mode so that it doesn't ask to save credit card details
	 */
	public function __construct() {
		$host = 'http://localhost:4444/';
		$options = new ChromeOptions();
		$options->addArguments(['--incognito']);
		$capabilities = DesiredCapabilities::chrome();
		$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
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
	 * set a field value, clearing any previous value it might have
	 * @param string $selector
	 * @param string $value
	 */
	public function sendKeys(string $selector, string $value) : void {
		$element = $this->driver->findElement(WebDriverBy::cssSelector($selector));
		$element->clear();
		$element->sendKeys($value);
	}

	/**
	 * set a field value via JavaScript, and trigger a change event to register the value change
	 * @param string $selector
	 * @param string $value
	 */
	public function setFieldValue(string $selector, string $value) : void {
		$script = sprintf('var f = document.querySelector(%s); f.value = %s; f.dispatchEvent(new Event("change"));', json_encode($selector), json_encode($value));
		$this->driver->executeScript($script);
	}

	/**
	 * set a select element selected option by value
	 * @param string $selector
	 * @param string $value
	 */
	public function selectByValue(string $selector, string $value) : void {
		$select = new WebDriverSelect($this->driver->findElement(WebDriverBy::cssSelector($selector)));
		$select->selectByValue($value);
	}

	/**
	 * set a select element selected option by the text label of an option
	 * @param string $selector
	 * @param string $text
	 */
	public function selectByText(string $selector, string $text) : void {
		$select = new WebDriverSelect($this->driver->findElement(WebDriverBy::cssSelector($selector)));
		$select->selectByVisibleText($text);
	}

	/**
	 * tick a checkbox element by index in a set matching a selector
	 * @param string $selector
	 * @param string $value
	 */
	public function checkboxTickByIndex(string $selector, int $index) : void {
		$boxes = new WebDriverCheckboxes($this->driver->findElement(WebDriverBy::cssSelector($selector)));
		$boxes->selectByIndex($index);
	}

	/**
	 * tick a checkbox element by value in a set matching a selector
	 * @param string $selector
	 * @param string $value
	 */
	public function checkboxTickByValue(string $selector, string $value) : void {
		$boxes = new WebDriverCheckboxes($this->driver->findElement(WebDriverBy::cssSelector($selector)));
		$boxes->selectByValue($value);
	}

	/**
	 * untick a checkbox element by index in a set matching a selector
	 * @param string $selector
	 * @param string $value
	 */
	public function checkboxUntickByIndex(string $selector, int $index) : void {
		$boxes = new WebDriverCheckboxes($this->driver->findElement(WebDriverBy::cssSelector($selector)));
		$boxes->deselectByIndex($index);
	}

	/**
	 * untick a checkbox element by value in a set matching a selector
	 * @param string $selector
	 * @param string $value
	 */
	public function checkboxUntickByValue(string $selector, string $value) : void {
		$boxes = new WebDriverCheckboxes($this->driver->findElement(WebDriverBy::cssSelector($selector)));
		$boxes->deselectByValue($value);
	}

	/**
	 * tick a radio button element by value in a set matching a selector
	 * @param string $selector
	 * @param string $value
	 */
	public function radioTickByValue(string $selector, string $value) : void {
		$boxes = new WebDriverRadios($this->driver->findElement(WebDriverBy::cssSelector($selector)));
		$boxes->selectByValue($value);
	}

}
