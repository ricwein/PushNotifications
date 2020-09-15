<?php

namespace ricwein\PushNotification;

use Exception;
use Throwable;

class Result
{
    /**
     * @var array<string, Exception|null>
     */
    private $feedback;

    public function __construct(array $feedback)
    {
        $this->feedback = $feedback;
    }

    public function getFailed(): array
    {
        $failed = [];
        foreach ($this->feedback as $token => $error) {
            if ($error !== null) {
                $failed[$token] = $error;
            }
        }
        return $failed;
    }

    /**
     * @throws Throwable
     */
    public function throwOnFirstError(): void
    {
        foreach ($this->feedback as $error) {
            if ($error instanceof Throwable) {
                throw $error;
            }
        }
    }

}
