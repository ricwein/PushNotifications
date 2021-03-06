<?php


namespace ricwein\PushNotification;


class Message
{
    /**
     * @var string|null
     */
    private ?string $title;

    /**
     * @var string
     */
    private string $body;

    /**
     * @var array
     */
    private array $payload;

    /**
     * @var string
     */
    private string $sound = Config::SOUND_DEFAULT;

    /** @var int */
    private int $badge = 1;

    /** @var int */
    private int $priority = Config::PRIORITY_HIGH;

    public function __construct(string $body, ?string $title = null, array $payload = [])
    {
        $this->body = $body;
        $this->title = $title;
        $this->payload = $payload;
    }

    public function setBadge(int $badge = 1): self
    {
        $this->badge = $badge;
        return $this;
    }

    public function setSound(string $sound = Config::SOUND_DEFAULT): self
    {
        $this->sound = $sound;
        return $this;
    }

    public function setPriority(int $priority = Config::PRIORITY_HIGH): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getBadge(): int
    {
        return $this->badge;
    }

    public function getSound(): string
    {
        return $this->sound;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
