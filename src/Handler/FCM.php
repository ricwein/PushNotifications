<?php

namespace ricwein\PushNotification\Handler;

use JsonException;
use ricwein\PushNotification\Config;
use ricwein\PushNotification\Exceptions\RequestException;
use ricwein\PushNotification\Exceptions\ResponseException;
use ricwein\PushNotification\Exceptions\ResponseReasonException;
use ricwein\PushNotification\Handler;
use ricwein\PushNotification\Message;

class FCM extends Handler
{
    public const FCM_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

    /**
     * @var string
     */
    private string $endpoint;

    /**
     * @var string
     */
    private string $token;

    /**
     * @var int
     */
    private int $timeout;

    public function __construct(string $token, string $url = self::FCM_ENDPOINT, int $timeout = 30)
    {
        $this->endpoint = $url;
        $this->token = $token;
        $this->timeout = $timeout;
    }

    /**
     * @param Message $message
     * @return array
     * @throws JsonException
     */
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

    /**
     * @param array $payload
     * @param int $priority
     * @return array
     * @throws JsonException
     */
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

        $content = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

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
            $response = curl_exec($curl);
            $httpStatusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

            if ($response === false || 200 !== $httpStatusCode) {
                $errorCode = curl_errno($curl);
                $error = curl_error($curl);
                return [new RequestException("[FCM ]Request failed with: [{$errorCode}]: {$error}", $httpStatusCode)];
            }

            $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if ($result === null || !isset($result['success'], $result['failure'], $result['results']) || !is_array($result['results'])) {
                return [new ResponseException("[FCM] Requests was send, but resulted in an unknown error. Response: {$response}", 400)];
            }

            $devicesCount = count($this->devices);
            if (((int)$result['failure'] + (int)$result['success']) !== $devicesCount || count($result['results']) !== $devicesCount) {
                return [new ResponseException(sprintf(
                    '[FCM] Mismatching Feedback Count. Message was send to %d devices, and %d succeeded and %d failed. %d reported an result.',
                    $devicesCount,
                    $result['success'],
                    $result['failure'],
                    count($result['results'])
                ), 400)];
            }

            return $this->parseAndBuildFeedback($result['results']);

        } finally {
            $this->devices = [];
            curl_close($curl);
        }
    }

    protected function parseAndBuildFeedback(array $results): array
    {
        $feedbackDevices = $this->devices;
        $feedback = [];

        foreach ($results as $messageFeedback) {
            $deviceToken = array_shift($feedbackDevices);

            if (!isset($messageFeedback['error'])) {
                $feedback[$deviceToken] = null;
                continue;
            }

            $error = $messageFeedback['error'];
            if (in_array($error, ResponseReasonException::GROUP_VALID_REASONS, true)) {
                $feedback[$deviceToken] = new ResponseReasonException($error, 400);
            } else {
                $feedback[$deviceToken] = new ResponseException("[FCM] Request failed with unknown error: {$error}", 400);
            }
        }

        return $feedback;
    }
}
