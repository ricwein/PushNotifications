<?php

require_once __DIR__ . '/../PushHandler.class.php';

class GCMHandler extends PushHandler {

	/**
	 * @var string
	 */
	protected $_server = [
		'key' => '',
		'url' => 'https://gcm-http.googleapis.com/gcm/send',
	];

	/**
	 * @param $message
	 * @param $data
	 * @return mixed
	 */
	public function send($message, $data = null) {

		if (count($this->_devices) == 0) {
			return false;
		}

		if (strlen($this->_server['key']) < 8) {
			throw new Exception('API key not set', 500);
		}

		$fields = [
			'registration_ids' => $this->_devices,
			'data'             => ['message' => $message],
		];

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$fields['data'][$key] = $value;
			}
		}

		$headers = [
			'Authorization: key=' . $this->_server['key'],
			'Content-Type: application/json',
		];

		// Open connection
		$curl = curl_init();

		// Set the url, number of POST vars, POST data
		curl_setopt($curl, CURLOPT_URL, $this->_server['url']);

		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));

		// Avoids problem with https certificate
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

		// Execute post
		$result = curl_exec($curl);

		// Close connection
		curl_close($curl);

		return @json_decode($result, true);
	}

}