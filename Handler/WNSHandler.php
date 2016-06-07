<?php
/**
 * @file   WSHandler.php
 * @brief  Handler for Windows-Push-Notification-Services
 *
 * @author  Richard Weinhold
 * @package  PushNotification
 */

namespace PushNotification\Handler;

use PushNotification\PushHandler;

class WNSHandler extends PushHandler {

	/**
	 * @var array
	 */
	protected $_server = [
		'token'    => '',
		'url'      => '',
		'auth-url' => 'https://login.live.com/accesstoken.srf',
	];

	/**
	 * @param $deviceID
	 * @param $deviceSecret
	 */
	public function requestOAuthToken($clientID, $clientSecret) {
		// init http-headers
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
		];

		// open curl connection
		$curl = curl_init();

		// set url
		curl_setopt($curl, CURLOPT_URL, $this->_server['auth-url']);

		// apply headers and set type to POST
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		// return response instead of status
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// check certificates
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

		// init payload
		$payload = 'grant_type=client_credentials&client_id=' . $clientID . '&client_secret=' . $clientSecret . '&scope=notify.windows.com';

		// append payload
		curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

		// send request
		$result = curl_exec($curl);

		if ($result === false) {
			$error = curl_error($curl);
			curl_close($curl);
			throw new \Exception('error processing WPN: ' . $error, 500);
		}

		$result = json_decode($result);

		// remeber to close the connection when finished
		curl_close($curl);

		// handle errors
		if (isset($output->error)) {
			throw new \Exception($output->error_description, 500);
		} elseif (!isset($result->access_token)) {
			throw new \Exception('access_token not found', 500);
		}

		return $result->access_token;
	}

	/**
	 * @param $message
	 * @param array $data
	 */
	protected static function _createPayload($message, array $data = null) {
		if (is_array($data) && isset($data['title']) && !isset($data['image'])) {
			return '<?xml version="1.0" encoding="utf-8"?>' .
				'<toast>' .
				'<visual>' .
				'<binding template="ToastText02">' .
				'<text id="1">' . $messages . '</text>' .
				'<text id="2">' . $data['title'] . '</text>' .
				'</binding>' .
				'</visual>' .
				'</toast>';
		} elseif (is_array($data) && isset($data['title'])) {
			return '<?xml version="1.0" encoding="utf-8"?>' .
				'<toast>' .
				'<visual>' .
				'<binding template="ToastImageAndText02">' .
				'<image id="1" src="' . $data['image'] . '" alt="' . $data['image'] . '"/>' .
				'<text id="1">' . $messages . '</text>' .
				'<text id="2">' . $data['title'] . '</text>' .
				'</binding>' .
				'</visual>' .
				'</toast>';
		} elseif (is_array($data) && isset($data['image'])) {
			return '<?xml version="1.0" encoding="utf-8"?>' .
				'<toast>' .
				'<visual>' .
				'<binding template="ToastImageAndText01">' .
				'<image id="1" src="' . $data['image'] . '" alt="' . $data['image'] . '"/>' .
				'<text id="1">' . $messages . '</text>' .
				'</binding>' .
				'</visual>' .
				'</toast>';
		} else {
			return '<?xml version="1.0" encoding="utf-8"?>' .
				'<toast>' .
				'<visual>' .
				'<binding template="ToastText01">' .
				'<text id="1">' . $messages . '</text>' .
				'</binding>' .
				'</visual>' .
				'</toast>';
		}}

	/**
	 * send notification to Microsofts live.com WNS servers
	 * @param string $message
	 * @param array $devices
	 * @param array $data (optional)
	 * @return bool
	 */
	public function send($message, array $devices, array $data = null) {
		$result = true;

		// init payload
		$payload = static::_createPayload($message, $data);

		// open curl connection
		$curl = curl_init();

		// set url
		curl_setopt($curl, CURLOPT_URL, $this->_server['url']);

		// set type to POST
		curl_setopt($curl, CURLOPT_POST, true);

		// return response instead of status
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// check certificates
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

		// append payload
		curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

		foreach ($devices as $clientID => $clientSecret) {

			// request device-token if necessary
			if (is_int($clientID)) {
				$token = $clientSecret;
			} else {
				$token = $this->requestOAuthToken($clientID, $clientSecret);
			}

			// init http-headers
			$headers = [
				'Authorization: Bearer ' . $token,
				'Content-Type: text/xml',
				'Content-Length: ' . strlen($payload),
				'X-WNS-Type: wns/toast',
			];

			if (isset($data['tag'])) {
				$headers[] = 'X-WNS-Tag: ' . $data['tag'];
			}

			// apply headers
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			// send request
			if (curl_exec($curl) === false) {
				throw new \Exception('error processing WPN: ' . curl_error($curl), 500);
			}

			$response = curl_getinfo($curl);
			$result   = $result && ((int) $response['http_code'] === 200);

		}

		// remeber to close the connection when finished
		curl_close($curl);

		return $result;
	}

}
