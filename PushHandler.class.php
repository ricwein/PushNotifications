<?php

abstract class PushHandler {

	/**
	 * @var array
	 */
	protected $_server = [
		'token' => '',
		'url'   => '',
	];

	/**
	 * @var array
	 */
	protected $_devices = [];

	/**
	 * @param string $serverToken (optional)
	 * @param string $url (optional)
	 */
	public function __construct($serverToken = null, $url = null) {
		if ($serverToken !== null) {
			$this->setServerToken($serverToken);
		}

		if ($url !== null) {
			$this->setUrl($url);
		}
	}

	/**
	 * @param string $url
	 */
	public function setUrl($url) {
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
	 * @param mixed $device
	 */
	public function addDevice($device) {
		$this->_devices = array_merge($this->_devices, (array) $device);
		return $this;
	}

	/**
	 * @param string $message
	 * @param mixed $data
	 */
	abstract public function send($message, $data = null);
}
