<?php
/**
 * @file   PushNotification.php
 * @brief   Push-Notification Wrapper
 *
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
	 * @param PushHandler $handler
	 */
	public function __construct(PushHandler $handler) {
		$this->_handler = $handler;
	}

	/**
	 * @param string $message
	 * @param array $data (optional)
	 * @return bool
	 */
	public function send($message, array $data = null) {
		if (!$this->_handler->prepare()) {
			return false;
		}
		return $this->_handler->send($message, $data);
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