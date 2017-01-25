<?php
/**
 * @author Richard Weinhold
 */

namespace ricwein\PushNotification;

/**
 * PushHandler, providing base push operations
 */
abstract class PushHandler {

	/**
	 * @var array
	 */
	protected $_server = [
		'token' => '',
		'url'   => '',
	];

	/**
	 * @param string $serverToken
	 * @param string $url
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

	/**
	 * check and prepare internal configuration for sending
	 * @return bool
	 * @throws \UnexpectedValueException
	 */
	public function prepare() {
		if (empty($this->_server['token'])) {
			throw new \UnexpectedValueException('server token not set', 500);
		} elseif (empty($this->_server['url'])) {
			throw new \UnexpectedValueException('server url not set', 500);
		}

		return true;
	}

	/**
	 * build default PushNotification and send via PushHandler to servers
	 * @param string $message
	 * @param array $payload
	 * @param array $devices
	 * @return bool
	 * @throws \UnexpectedValueException|\RuntimeException
	 */
	abstract public function send($message, array $payload = [], array $devices);

	/**
	 * build and send Notification from raw payload
	 * @param  array $payload
	 * @param  array $devices
	 * @return bool
	 * @throws \UnexpectedValueException|\RuntimeException
	 */
	abstract public function sendRaw(array $payload, array $devices);
}
