<?php
/**
 * @file   PushHandler.php
 * @brief   abstract base-class for Push-Notification Handlers
 *
 * @author  Richard Weinhold
 * @package  PushNotification
 */

namespace PushNotification;

abstract class PushHandler {

	/**
	 * @var array
	 */
	protected $_server = [
		'token' => '',
		'url'   => '',
	];

	/**
	 * @param string $serverToken (optional)
	 * @param string $url (optional)
	 */
	public function __construct($serverToken = null, $url = null) {
		if ($serverToken !== null) {
			$this->setServerToken($serverToken);
		}

		if ($url !== null) {
			$this->setServerUrl($url);
		}
	}

	/**
	 * @param string $url
	 */
	public function setServerUrl($url) {
		$this->_server['url'] = $url;
		return $this;
	}

	/**
	 * @param string $serverToken
	 */
	public function setServerToken($serverToken) {
		$this->_server['token'] = $serverToken;
		return $this;
	}

	/**
	 * @param array $server
	 */
	public function setServer(array $server) {
		$this->_server = array_merge($this->_server, $server);
		return $this;
	}

	public function prepare() {
		if (empty($this->_server['token'])) {
			throw new \Exception('server token not set', 500);
		} elseif (empty($this->_server['url'])) {
			throw new \Exception('server url not set', 500);
		}

		return true;
	}

	/**
	 * build payload and send via push-handler to servers
	 * @param string $message
	 * @param array $devices
	 * @param array $data (optional)
	 * @return bool
	 */
	abstract public function send($message, array $data = [], array $devices);
}
