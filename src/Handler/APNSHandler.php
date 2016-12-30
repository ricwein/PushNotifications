<?php
/**
 * @author Richard Weinhold
 */

namespace ricwein\PushNotification\Handler;

use ricwein\PushNotification\PushHandler;

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
	 * @param array $payload (optional)
	 * @param array $devices
	 * @return bool
	 */
	public function send($message, array $payload = [], array $devices) {
		$result    = true;
		$arbitrary = ['command' => 1];

		// handle arbitrary settings
		foreach (['expire', 'messageID', 'priority', 'command'] as $key) {
			if (isset($payload[$key])) {
				$arbitrary[$key] = (int) abs($payload[$key]);
				unset($payload[$key]);
			} elseif (isset($this->_server[$key])) {
				$arbitrary[$key] = (int) abs($this->_server[$key]);
			}
		}

		// build payload
		$payload = array_merge(['aps' => [
			'alert' => trim(stripslashes($message)),
			'badge' => 1,
			'sound' => 'default',
		]], $payload);

		// open context
		$ctx = stream_context_create();

		// check and set cert-path
		$certpath = realpath($this->_server['token']);
		if (empty($certpath) || $certpath === DIRECTORY_SEPARATOR || !is_file($certpath)) {
			throw new \UnexpectedValueException('Invalid cert-file: ' . $certpath, 500);
		}
		stream_context_set_option($ctx, 'ssl', 'local_cert', $certpath);

		// set cert passphrase if given
		if ($this->_server['passphrase'] !== null) {
			stream_context_set_option($ctx, 'ssl', 'passphrase', $this->_server['passphrase']);
		}

		// open tcp-stream to server
		$stream = @stream_socket_client($this->_server['url'], $errno, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

		if (!$stream) {
			throw new \RuntimeException('Error connecting to APNS-Server [' . $errno . ']: ' . $errstr, 500);
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

		// set default arbitrary settings
		$arbitrary = array_merge([
			'expire'    => 0,
			'messageID' => 0,
			'priority'  => 10,
		], $arbitrary);

		// cleanup device tokens
		$deviceToken = str_replace(' ', '', trim($deviceToken, '<> '));

		// build notification
		if ((int) $version === 1) {

			$notification = pack('C', 1); // Command 1
			$notification .= pack('N', (int) $arbitrary['messageID']); // notification id
			$notification .= pack('N', ($arbitrary['expire'] > 0 ? time() + $arbitrary['expire'] : 0)); // expiration timestamps
			$notification .= pack('nH*', 32, $deviceToken); // device-token
			$notification .= pack('n', strlen($payload)) . $payload; // payload

			return $notification;
		} elseif ((int) $version === 2) {

			// build notification
			$notification = pack('CnH*', 1, 32, $deviceToken); // device-token
			$notification .= pack('CnA*', 2, strlen($payload), $payload); // payload
			$notification .= pack('CnN', 3, 4, (int) $arbitrary['messageID']); // notification id
			$notification .= pack('CnN', 4, 4, ($arbitrary['expire'] > 0 ? time() + $arbitrary['expire'] : 0)); // expiration timestamps
			$notification .= pack('CnC', 5, 1, (int) $arbitrary['priority']); // notification priority

			// pack notification into frame
			$frame = pack('C', 2); // Command 2
			$frame .= pack('N', strlen($notification)) . $notification; // notification

			return $frame;
		}

		throw new \UnexpectedValueException('Unknown Command Version', 500);
	}

}
