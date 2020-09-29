<?php
/**
 * @author Richard Weinhold
 */

namespace ricwein\PushNotification;

use Exception;
use RuntimeException;

/**
 * PushHandler, providing base push operations
 */
abstract class Handler
{

    /**
     * @var string[]
     */
    protected array $devices = [];
    protected ?string $caCertPath;

    protected function setCaCertPath(?string $caCertPath): void
    {
        if ($caCertPath !== null) {
            $this->caCertPath = $caCertPath;
        } elseif (class_exists('\Composer\CaBundle\CaBundle')) {
            $this->caCertPath = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        }
    }

    /**
     * @return array|null
     */
    protected function getCurlCAPathOptions(): ?array
    {
        if ($this->caCertPath === null) {
            return null;
        }

        $caCertPath = realpath($this->caCertPath);
        if ($caCertPath === null || !file_exists($caCertPath) || !is_readable($caCertPath)) {
            throw new RuntimeException("CA not found or not readable for path: {$this->caCertPath}", 404);
        }

        if (is_dir($caCertPath)) {
            return [CURLOPT_CAPATH => $caCertPath];
        }

        return [CURLOPT_CAINFO => $caCertPath];
    }

    public function addDevice(string $token): void
    {
        $this->devices[] = $token;
    }

    /**
     * @param Message $message
     * @return array<string, Exception|null>
     */
    abstract public function send(Message $message): array;

    /**
     * @param array $payload
     * @param int $priority
     * @return array<string, Exception|null>
     */
    abstract public function sendRaw(array $payload, int $priority = Config::PRIORITY_HIGH): array;
}
