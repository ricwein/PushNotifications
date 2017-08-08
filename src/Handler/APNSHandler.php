<?php
/**
 * @author Richard Weinhold
 */
namespace ricwein\PushNotification\Handler;

use ricwein\PushNotification\PushHandler;

/**
 * PushHandler for Apple Push Notification Service
 */
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
     * build binary notification-package
     *
     * @param string $deviceToken
     * @param string $payload     json
     * @param array  $arbitrary   additional settings
     * @param int    $version     push-version (1/2)
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    protected function _buildNotification(string $deviceToken, string $payload, array $arbitrary = [], int $version = 1): string {

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

    /**
     * send notification to Apples APNS servers
     *
     * @param string      $message
     * @param string|null $title
     * @param array       $payload
     * @param array       $devices
     *
     * @throws \UnexpectedValueException|\RuntimeException
     *
     * @return bool
     */
    public function send(string $message, string $title = null, array $payload, array $devices): bool {
        $message = trim(stripslashes($message));

        // build payload
        $payload = array_replace_recursive(['aps' => [
            'alert' => $title !== null ? ['title' => $title, 'body' => $message] : $message,
            'badge' => 1,
            'sound' => 'default',
        ]], $payload);

        return $this->sendRaw($payload, $devices);
    }

    /**
     * build and send Notification from raw payload
     *
     * @param array $payload
     * @param array $devices
     *
     * @throws \UnexpectedValueException|\RuntimeException
     *
     * @return bool
     */
    public function sendRaw(array $payload, array $devices): bool {

        // set default values
        $result    = true;
        $arbitrary = ['command' => 1];

        // extract arbitrary settings
        foreach (['expire', 'messageID', 'priority', 'command'] as $key) {
            if (isset($payload[$key])) {
                $arbitrary[$key] = (int) abs($payload[$key]);
                unset($payload[$key]);
            } elseif (isset($this->_server[$key])) {
                $arbitrary[$key] = (int) abs($this->_server[$key]);
            }
        }

        // open context
        $ctx = stream_context_create();

        // check and set cert-path
        $certpath = realpath($this->_server['token']);
        if (empty($certpath) || $certpath === DIRECTORY_SEPARATOR || !is_file($certpath)) {
            throw new \UnexpectedValueException('Invalid cert-file: ' . $certpath, 500);
        }

        if (!stream_context_set_option($ctx, 'ssl', 'local_cert', $certpath)) {
            throw new \UnexpectedValueException('unable to set cert-file', 500);
        }

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
}
