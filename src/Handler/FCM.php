<?php

namespace ricwein\PushNotification\Handler;

use ricwein\PushNotification\Config;
use ricwein\PushNotification\Handler;
use ricwein\PushNotification\Message;
use RuntimeException;

class FCM extends Handler
{
    public const FCM_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $timeout;

    public function __construct(string $token, string $url = self::FCM_ENDPOINT, int $timeout = 10)
    {
        $this->endpoint = $url;
        $this->token = $token;
        $this->timeout = $timeout;
    }

    public function send(Message $message): array
    {
        if (count($this->devices) < 1) {
            return [];
        }

        $body = trim(stripslashes($message->getBody()));

        // build payload
        $payload = [
            'notification' => [
                'title' => $message->getTitle() ?? (strlen($body) > 64 ? substr($body, 0, 61) . '...' : $body),
                'body' => $message,
            ],
            'data' => array_merge([
                'message' => $message,
            ], $message->getPayload()),
        ];

        return $this->sendRaw($payload, $message->getPriority());
    }

    public function sendRaw(array $payload, int $priority = Config::PRIORITY_HIGH): array
    {
        if (count($this->devices) < 1) {
            return [];
        }

        if (count($this->devices) <= 1) {
            $payload = array_merge([
                'to' => current($this->devices),
            ], $payload);
        } else {
            $payload = array_merge([
                'registration_ids' => $this->devices,
            ], $payload);
        }

        $payload = array_merge([
            'priority' => $priority === Config::PRIORITY_HIGH ? 'high' : 'normal',
        ], $payload);

        $content = json_encode($payload, JSON_UNESCAPED_UNICODE);


        $headers = [
            "Authorization: key={$this->token}",
            "Content-Type: application/json",
        ];

        $options = [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
        ];

        $curl = curl_init();

        try {
            curl_setopt_array($curl, $options);

            // execute request
            $result = curl_exec($curl);
            $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($result === false || 200 !== $httpStatusCode) {
                $errorCode = curl_errno($curl);
                $error = curl_error($curl);
                return [new RuntimeException("Request failed with: [{$errorCode}]: {$error}", $httpStatusCode)];
            }

            $result = @json_decode($result, true);
            if (!isset($result['success']) || (int)$result['success'] !== count($this->devices)) {
                return [new RuntimeException("Requests was send, but resulted in an error.", 400)];
            }

            return [null];
        } finally {
            curl_close($curl);
        }
    }
}
