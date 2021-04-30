<?php

namespace ricwein\PushNotification\Handler;

use Exception;
use Pushok\AuthProviderInterface;
use Pushok\Client;
use Pushok\InvalidPayloadException;
use Pushok\Notification;
use Pushok\Payload;
use Pushok\Response;
use ricwein\PushNotification\Config;
use ricwein\PushNotification\Exceptions\RequestException;
use ricwein\PushNotification\Exceptions\ResponseReasonException;
use ricwein\PushNotification\Handler;
use ricwein\PushNotification\Message;
use RuntimeException;

class APNS extends Handler
{
    private string $environment;
    private AuthProviderInterface $authProvider;

    public function __construct(AuthProviderInterface $authProvider, string $environment = Config::ENV_PRODUCTION)
    {
        $this->environment = $environment;
        $this->authProvider = $authProvider;
    }

    public function addDevice(string $token): void
    {
        if (64 !== $length = strlen($token)) {
            throw new RuntimeException("[APNS] Invalid device-token $token, length must be 64 chars but is $length.", 500);
        }
        if (!ctype_xdigit($token)) {
            throw new RuntimeException("[APNS] Invalid device-token $token, must be of type hexadecimal but is not.");
        }
        $this->devices[] = $token;
    }

    /**
     * @param Message $message
     * @return array
     * @throws InvalidPayloadException
     * @throws RequestException
     */
    public function send(Message $message): array
    {
        if (count($this->devices) < 1) {
            return [];
        }

        $payload = array_merge_recursive([
            'aps' => [
                'alert' => $message->getTitle() !== null ? ['title' => $message->getTitle(), 'body' => $message->getBody()] : $message->getBody(),
                'badge' => $message->getBadge(),
                'sound' => $message->getSound(),
            ]
        ], $message->getPayload());

        return $this->sendRaw($payload, $message->getPriority());
    }

    /**
     * @throws InvalidPayloadException
     * @throws RequestException
     */
    public function sendRaw(array $payload, int $priority = Config::PRIORITY_HIGH): array
    {
        $messagePayload = Payload::create();
        foreach ($payload as $key => $value) {
            $messagePayload->addCustomValue($key, $value);
        }

        $notifications = array_map(static function (string $deviceToken) use ($priority, $messagePayload): Notification {
            $notification = new Notification($messagePayload, $deviceToken);
            if ($priority === Config::PRIORITY_HIGH) {
                $notification->setHighPriority();
            } else {
                $notification->setLowPriority();
            }
            return $notification;
        }, $this->devices);

        $client = new Client($this->authProvider, $this->environment === Config::ENV_PRODUCTION);
        $client->addNotifications($notifications);

        try {
            $responses = $client->push(); // returns an array of ApnsResponseInterface (one Response per Notification)
        } catch (Exception $exception) {
            throw new RequestException('[APNS] Request failed', $exception->getCode(), $exception);
        }

        $feedback = [];
        /** @var Response $response */
        foreach ($responses as $response) {
            if ($response->getStatusCode() === Response::APNS_SUCCESS) {
                $feedback[$response->getDeviceToken()] = null;
                continue;
            }

            $feedback[$response->getDeviceToken()] = new ResponseReasonException($response->getErrorReason(), $response->getStatusCode());
        }

        $this->devices = [];
        return $feedback;
    }
}
