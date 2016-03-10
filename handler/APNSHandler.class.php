<?php

require_once __DIR__ . '/../PushHandler.class.php';

class APNSHandler extends PushHandler {

	/**
	 * @var string
	 */
	protected $_server = [
		'token'      => '',
		'url'        => 'ssl://gateway.push.apple.com:2195 ',
		'passphrase' => null,
	];

	/**
	 * @param $message
	 * @param $data
	 * @return mixed
	 */
	public function send($message, $data = null) {

		// generate payload
		$payload = ['aps' => [
			'alert' => stripslashes($message),
			'badge' => 1,
			'sound' => 'bingbong.aiff',
		]];

		if (is_array($data)) {
			$payload['aps'] = array_merge($payload['aps'], $data);
		}

		$ctx = stream_context_create();

		$certpath = realpath($this->_server['token']);
		if (empty($certpath) || $certpath === DIRECTORY_SEPARATOR || !is_file($certpath)) {
			throw new Exception('invalid cert-file: ' . $certpath, 500);
		}
		stream_context_set_option($ctx, 'ssl', 'local_cert', $certpath);

		if ($this->_server['passphrase'] !== null) {
			stream_context_set_option($ctx, 'ssl', 'passphrase', $this->_server['passphrase']);
		}

		$stream = @stream_socket_client($this->_server['url'], $errno, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

		if (!$stream) {
			throw new Exception('error processing APNS [' . $errno . ']:' . $errstr, 500);
		}

		$payload = json_encode($payload);

		foreach ($this->_devices as $device) {
			$notification = chr(0) . pack('n', 32) . pack('H*', str_replace(' ', '', $device)) . pack('n', strlen($payload)) . $payload;
			fwrite($stream, $notification);
		}

		fclose($stream);

	}

}
