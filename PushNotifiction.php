<?php

require_once __DIR__ . '/handler/GCMHandler.class.php';

class PushNotification {

	/**
	 * @var mixed
	 */
	protected $_handler = NULL;

	/**
	 * @param $handler
	 */
	public function __construct(PushHandler $handler) {
		$this->_handler = $handler;
	}

	/**
	 * @param $name
	 * @param $arguments
	 */
	public function __call($name, $arguments) {

		if (method_exists($this->_handler, $name)) {
			call_user_func_array([$this->_handler, $name], $arguments);
		} else {
			throw new Exception('unknown ' . $name . ' call in PushNotification', 500);
		}

		return $this;
	}
}