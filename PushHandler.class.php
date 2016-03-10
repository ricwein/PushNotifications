<?php

abstract class PushHandler {

	/**
	 * @var array
	 */
	protected $_server = [
		'key' => '',
		'url' => '',
	];

	/**
	 * @var array
	 */
	protected $_devices = [];

	/**
	 * @param string $apiKey (optional)
	 * @param string $url (optional)
	 */
	public function __construct($apiKey = null, $url = null) {
		if ($apiKey !== null) {
			$this->setServerKey($apiKey);
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
	 * @param string $apiKey
	 */
	public function setServerKey($apiKey) {
		$this->_server['key'] = $apiKey;
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
