<?php


namespace ricwein\PushNotification\Handler;


use ricwein\PushNotification\Config;
use ricwein\PushNotification\Handler;
use ricwein\PushNotification\Message;

class Dummy extends Handler
{
    /**
     * @var callable|null
     */
    private $callback;

    /**
     * Dummy constructor.
     * @param callable|null $sendCallback function(array $payload, string $deviceToken)
     */
    public function __construct(?callable $sendCallback = null)
    {
        $this->callback = $sendCallback;
    }

    protected function resetAndBuildFeedback(): array
    {
        if (count($this->devices) < 1) {
            return [];
        }

        $feedback = array_fill(0, count($this->devices) - 1, null);
        $this->devices = [];

        return $feedback;
    }

    public function send(Message $message): array
    {
        if ($this->callback === null) {
            return $this->resetAndBuildFeedback();
        }

        $payload = [
            'message' => [
                'title' => $message->getTitle(),
                'body' => $message->getBody(),
            ],
            'payload' => $message->getPayload(),
        ];

        return $this->sendRaw($payload, $message->getPriority());
    }

    public function sendRaw(array $payload, int $priority = Config::PRIORITY_HIGH): array
    {
        if ($this->callback === null) {
            return $this->resetAndBuildFeedback();
        }

        $payload = array_merge($payload, [
            'priority' => $priority === Config::PRIORITY_HIGH ? 'high' : 'normal',
        ]);

        foreach ($this->devices as $deviceToken) {
            call_user_func($this->callback, $payload, $deviceToken);
        }

        return $this->resetAndBuildFeedback();
    }
}
