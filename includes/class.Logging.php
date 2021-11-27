<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * simple logging for plugin
 */
class Logging {

	protected $logFolder;
	protected $integration;
	protected $min_level;
	protected $status;
	protected $handle;

	/**
	 * initialise logging
	 */
	public function __construct(string $integration, string $level) {
		$this->integration = $integration;
		$this->min_level   = $level;
		$this->status	   = 'new';

		// attempt to locate or create log folder
		$logFolder = self::getLogFolder();
		if ($logFolder) {
			if (is_dir($logFolder)) {
				// log folder already exists
				$this->logFolder = $logFolder;
			}
			else {
				$base = dirname($logFolder);
				if (!is_dir($base)) {
					// need to create parent folder
					if (mkdir($base, 0755)) {
						// prevent web access to index of folder
						// phpcs:disable Generic.PHP.NoSilencedErrors.Discouraged
						@file_put_contents($base . '/.htaccess', "Options -Indexes\n");
						@touch($base . '/index.html');
						// phpcs:enable
					}
				}
				// create log folder if parent folder was created OK
				if (is_dir($base) && mkdir($logFolder, 0755)) {
					@touch($logFolder . '/index.html');  // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
					$this->logFolder = $logFolder;
				}
			}
		}
	}

	/**
	 * close any files we opened
	 */
	public function __destruct() {
		if ($this->status === 'opened') {
			fclose($this->handle);
		}
	}

	/**
	 * write to the log
	 * @param string $level
	 * @param string $msg
	 */
	public function log(string $level, string $msg) {
		if (empty($this->logFolder)) {
			// no log folder (e.g. error creating folder)
			return;
		}

		if ($this->min_level === 'off') {
			// no logging
			return;
		}

		if ($this->min_level === 'error' && $level !== 'error') {
			// skip logging for this message level
			return;
		}

		// if log hasn't been opened / created, do it now
		if ($this->status === 'new') {
			$handle = fopen("{$this->logFolder}/{$this->integration}.log", 'a');
			if ($handle) {
				$this->handle = $handle;
				$this->status = 'opened';
			}
			else {
				$this->status = 'failed';
			}
		}

		// if log was successfully opened / created, write message to log
		if ($this->status === 'opened') {
			$line = sprintf("%s\t%s\t%s\n", gmdate('Y-m-d G:i:s'), $level, self::sanitiseLog($msg));
			fwrite($this->handle, $line);
		}
	}

	/**
	 * sanitise a logging message to obfuscate credit card details before storing in plain text!
	 */
	protected static function sanitiseLog(string $message) : string {
		// obfuscate anything that looks like credit card number: a string of at least 12 numeric digits
		$message = preg_replace('#[0-9]{8,}([0-9]{4})#', '************$1', $message);

		// obfuscate encrypted card details
		$message = preg_replace('#eCrypted:\S+#', '[encrypted]', $message);

		return $message;
	}

	/**
	 * get indiscoverable folder name for log files
	 */
	public static function getLogFolder() : string {
		static $logFolder = null;

		if (is_null($logFolder)) {
			$upload_dir = wp_upload_dir();

			if (empty($upload_dir['error'])) {
				$logFolder = sprintf('%s/eway-payment-gateway/%s', $upload_dir['basedir'], wp_hash('eway-payment-gateway'));
			}
			else {
				$logFolder = false;
			}
		}

		return $logFolder;
	}

	/**
	 * get relative path to log folder
	 */
	public static function getLogFolderRelative() : string {
		return substr(self::getLogFolder(), strlen(ABSPATH));
	}

}
