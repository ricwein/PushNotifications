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

		// default arbitrary settings
		$arbitrary = [
			'expire'    => 0,
			'messageID' => 0,
			'priority'  => 10,
			'command'   => 1,
		];

		// handle arbitrary settings
		foreach (['expire', 'messageID', 'priority', 'command'] as $key) {

			if (isset($data[$key])) {
				$arbitrary[$key] = (int) abs($data[$key]);
				unset($data[$key]);
			} elseif (isset($this->_server[$key])) {
				$arbitrary[$key] = (int) abs($this->_server[$key]);
			}

		}

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

			// build binary notification
			$notification = $this->_buildNotification($device, $payload, $arbitrary, $arbitrary['command']);

			// write into stream and apply result onto previous results
			$result = $result && (bool) fwrite($stream, $notification);
		}

		// remeber to close the stream when finished
		@fclose($stream);

		return $result;
	}

}

/**
 * build binary notification-package
 * @param string $device
 * @param string $payload json
 * @param int $expiration (optional)
 * @param array $arbitrary additional settings
 * @param int $version push-version (1/2)
 * @return string
 */
protected function _buildNotification($deviceToken, $payload, array $arbitrary = [], $version = 1) {
	$tokenLength = 32;

	// cleanup device tokens
	$deviceToken = str_replace(' ', '', trim($deviceToken, '<> '));

	// build notification
	if ((int) $version === 1) {

		$notification = pack('C', 1); // Command 1
		$notification .= pack('N', (int) $arbitrary['messageID']); // notification id
		$notification .= pack('N', ($arbitrary['expire'] > 0 ? time() + $arbitrary['expire'] : 0)); // expiration timestamps
		$notification .= pack('n', $tokenLength); // token length
		$notification .= pack('H*', $deviceToken); // device-token
		$notification .= pack('n', strlen($payload)); // payload-length
		$notification .= $payload; // payload

		return $notification;
	} elseif ((int) $version === 2) {

		if (!isset($arbitrary['priority']) || !is_int($arbitrary['priority'])) {
			$arbitrary['priority'] = 10;
		}

		// build notification first
		$notification = pack('C', 1); // head
		$notification .= pack('n', $tokenLength); // token length
		$notification .= pack('H*', $deviceToken); // device-token
		$notification .= pack('C', 2);
		$notification .= pack('n', strlen($payload)); // payload-length
		$notification .= pack('A*', $payload); // payload
		$notification .= pack('Cn', 3, 4);
		$notification .= pack('N', (int) $arbitrary['messageID']); // notification id
		$notification .= pack('Cn', 4, 4);
		$notification .= pack('N', ($arbitrary['expire'] > 0 ? time() + $arbitrary['expire'] : 0)); // expiration timestamps
		$notification .= pack('Cn', 5, 1);
		$notification .= pack('C', (int) $arbitrary['priority']); // notification priority

		// pack notification into frame
		$frame = pack('C', 2); // Command 2
		$frame .= pack('N', strlen($notification)); // notification length
		$frame .= $notification; // notification

		return $frame;
	} else {
		throw new \Exception('unknown Command version', 500);
	}

}
