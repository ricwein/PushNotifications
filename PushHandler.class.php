<?php

abstract class PushHandler {

	/**
	 * @var string
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
	 * @param $apiKey
	 * @param NULL $url
	 */
	public function __construct($apiKey = NULL, $url = NULL) {
		if ($apiKey !== NULL) {
			$this->setServerKey($apiKey);
		}

		if ($url !== NULL) {
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
	 * @param $device
	 */
	public function addDevice($device) {
		$this->_devices = array_merge($this->_devices, (array) $device);
		return $this;
	}

	/**
	 * @param string $message
	 * @param mixed $data
	 */
	abstract public function send($message, $data = false) {return false;}
}
