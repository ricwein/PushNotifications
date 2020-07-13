<?php
/**
 * @author Richard Weinhold
 */

namespace ricwein\PushNotification\Handler;

use ricwein\PushNotification\PushHandler;
use RuntimeException;
use UnexpectedValueException;

/**
 * PushHandler for Windows push Notification Services
 */
class WNSHandler extends PushHandler
{

    /**
     * @var array
     */
    protected $_server = [
        'token' => '',
        'url' => '',
        'auth-url' => 'https://login.live.com/accesstoken.srf',
    ];

    /**
     * @param int $clientID
     * @param string $clientSecret
     * @return string
     * @throws RuntimeException
     */
    public function requestOAuthToken(int $clientID, string $clientSecret): string
    {
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
        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('error processing WPN: ' . $error, 500);
        }

        $result = json_decode($response, true);

        // remeber to close the connection when finished
        curl_close($curl);

        // handle errors
        if (isset($result['error'], $result['error_description'])) {
            throw new RuntimeException("[{$result['error']}] - {$result['error_description']}", 500);
        }

        if (!isset($result['access_token'])) {
            throw new RuntimeException('access_token not found', 500);
        }

        return $result['access_token'];
    }

    /**
     * @param string $message
     * @param array $data
     * @return string
     */
    protected static function _buildPayloadXML(string $message, array $data = []): string
    {
        $message = trim(stripslashes($message));

        if (isset($data['title']) && !isset($data['image'])) {
            return "<?xml version=\"1.0\" encoding=\"utf-8\"?><toast><visual><binding template=\"ToastText02\"><text id=\"1\">{$message}</text><text id=\"2\">{$data['title']}</text></binding></visual></toast>";
        }

        if (isset($data['title'])) {
            return "<?xml version=\"1.0\" encoding=\"utf-8\"?><toast><visual><binding template=\"ToastImageAndText02\"><image id=\"1\" src=\"{$data['image']}\" alt=\"{$data['image']}\"/><text id=\"1\">{$message}</text><text id=\"2\">{$data['title']}</text></binding></visual></toast>";
        }

        if (isset($data['image'])) {
            return "<?xml version=\"1.0\" encoding=\"utf-8\"?><toast><visual><binding template=\"ToastImageAndText01\"><image id=\"1\" src=\"{$data['image']}\" alt=\"{$data['image']}\"/><text id=\"1\">{$message}</text></binding></visual></toast>";
        }

        return "<?xml version=\"1.0\" encoding=\"utf-8\"?><toast><visual><binding template=\"ToastText01\"><text id=\"1\">{$message}</text></binding></visual></toast>";
    }

    /**
     * send notification to Microsoft live.com WNS servers
     * @param string $message
     * @param string|null $title
     * @param array $payload
     * @param array $devices
     * @return bool
     * @throws UnexpectedValueException
     * @throws RuntimeException
     */
    public function send(string $message, ?string $title, array $payload, array $devices): bool
    {
        if ($title !== null) {
            $payload['title'] = $title;
        }

        // buil xml-payload
        $payload['xml'] = static::_buildPayloadXML($message, $payload);

        return $this->sendRaw($payload, $devices);
    }

    /**
     * build and send Notification from raw payload
     * @param array $payload
     * @param array $devices
     * @return bool
     * @throws RuntimeException|UnexpectedValueException
     */
    public function sendRaw(array $payload, array $devices): bool
    {

        // buil xml-payload
        if (isset($payload['xml'])) {
            $xml = $payload['xml'];
        } elseif (isset($payload['message'])) {
            $xml = static::_buildPayloadXML($payload['message'], $payload);
        } else {
            throw new UnexpectedValueException('missing \'messages\' or \'xml\' key for WNS payload', 500);
        }

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
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);

        $result = true;

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
                'Content-Length: ' . strlen($xml),
                'X-WNS-Type: wns/toast',
            ];

            if (isset($payload['tag'])) {
                $headers[] = 'X-WNS-Tag: ' . $payload['tag'];
            }

            // apply headers
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            // send request
            if (curl_exec($curl) === false) {
                $error = curl_error($curl);
                curl_close($curl);
                throw new RuntimeException('error processing WPN: ' . $error, 500);
            }

            $response = curl_getinfo($curl);
            $result = $result && ((int)$response['http_code'] === 200);
        }

        // remeber to close the connection when finished
        curl_close($curl);

        return $result;
    }
}
