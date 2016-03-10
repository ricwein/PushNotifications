<?php

require_once __DIR__ . '/handler/GCMHandler.class.php';
require_once __DIR__ . '/handler/APNSHandler.class.php';

class PushNotification {

	/**
	 * @var PushHandler
	 */
	protected $_handler = null;

	/**
	 * @param PushHandler $handler
	 */
	public function __construct(PushHandler $handler) {
		$this->_handler = $handler;
	}

	/**
	 * @param string $message
	 * @param array $data (optional)
	 */
	public function send($message, $data = null) {
		return $this->_handler->send($message, $data);
	}

	/**
	 * wraps handler-methods, and returns $this
	 * @param string $name
	 * @param mixed $arguments
	 * @return $this
	 */
	public function __call($name, $arguments) {

		if (method_exists($this->_handler, $name)) {
			call_user_func_array([$this->_handler, $name], $arguments);
		} else {
			throw new Exception('unknown ' . $name . ' call for ' . get_class($this->_handler), 500);
		}

		return $this;
	}
}