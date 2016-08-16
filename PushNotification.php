<?php
/**
 * @author  Richard Weinhold
 * @package  PushNotification
 */

namespace PushNotification;

use PushNotification\PushHandler;

class PushNotification {

	/**
	 * @var PushHandler
	 */
	protected $_handler = null;

	/**
	 * @var array
	 */
	protected $_devices = [];

	/**
	 * @param PushHandler $handler (optional)
	 */
	public function __construct(PushHandler $handler = null) {
		if ($handler !== null) {
			$this->setHandler($handler);
		}
	}

	/**
	 * @param PushHandler $handler
	 * @return PushNotification
	 */
	public function setHandler(PushHandler $handler) {
		$this->_handler = $handler;
		return $this;
	}

	/**
	 * build payload and send via push-handler to servers
	 * @param string $message
	 * @param array $payload (optional)
	 * @return bool
	 */
	public function send($message, array $payload = []) {
		if (!$this->_prepare()) {
			return false;
		}
		return $this->_handler->send($message, $payload, $this->_devices);
	}

	protected function _prepare() {
		if (count($this->_devices) === 0) {
			return false;
		} elseif (!$this->_handler->prepare()) {
			return false;
		}

		return true;
	}

	/**
	 * @param mixed $device
	 */
	public function addDevice($device) {
		$this->_devices = array_merge($this->_devices, (array) $device);
		return $this;
	}

	/**
	 * wrap handler-methods and return $this
	 * @param string $name
	 * @param mixed $arguments
	 * @return $this
	 */
	public function __call($name, $arguments) {

		if (method_exists($this->_handler, $name)) {
			call_user_func_array([$this->_handler, $name], $arguments);
		} else {
			throw new \Exception('unknown call to ' . get_class($this->_handler) . '->' . $name . '()', 500);
		}

		return $this;
	}
}
