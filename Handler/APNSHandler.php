<?php
/**
 * @file   APNSHandler.php
 * @brief  Handler for Apple-Push-Notifications
 *
 * @author  Richard Weinhold
 * @package  PushNotification
 */

namespace PushNotification\Handler;

use PushNotification\PushHandler;

class APNSHandler extends PushHandler {

	/**
	 * @var array
	 */
	protected $_server = [
		'token'      => '',
		'url'        => 'ssl://gateway.push.apple.com:2195',
		'passphrase' => null,
	];

	/**
	 * send notification to Apples APNS servers
	 * @param string $message
	 * @param array $devices
	 * @param array $data (optional)
	 * @return bool
	 */
	public function send($message, array $devices, array $data = null) {
		$result = true;

		// init payload
		$payload = ['aps' => [
			'alert' => stripslashes($message),
			'badge' => 1,
			'sound' => 'default',
		]];

		// apply additional data to payload
		if (is_array($data)) {
			$payload['aps'] = array_merge($payload['aps'], $data);
		}

		// open context
		$ctx = stream_context_create();

		// check and set cert-path
		$certpath = realpath($this->_server['token']);
		if (empty($certpath) || $certpath === DIRECTORY_SEPARATOR || !is_file($certpath)) {
			throw new \Exception('invalid cert-file: ' . $certpath, 500);
		}
		stream_context_set_option($ctx, 'ssl', 'local_cert', $certpath);

		// set cert passphrase if required
		if ($this->_server['passphrase'] !== null) {
			stream_context_set_option($ctx, 'ssl', 'passphrase', $this->_server['passphrase']);
		}

		// open tcp-stream to server
		$stream = @stream_socket_client($this->_server['url'], $errno, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

		if (!$stream) {
			throw new \Exception('error processing APNS [' . $errno . ']:' . $errstr, 500);
		}

		$payload = json_encode($payload);

		// create and write notification for each single device
		foreach ($devices as $device) {

			// cleanup device tokens
			$device = trim($device, '<>');
			$device = str_replace(' ', '', $device);

			// convert hex-tokens into binary
			$notification = chr(0) . pack('n', 32) . pack('H*', $device) . pack('n', strlen($payload)) . $payload;

			// write into stream and apply result onto previous results
			$result = $result && (bool) fwrite($stream, $notification);
		}

		// remeber to close the stream when finished
		@fclose($stream);

		return $result;
	}

}
