<?php
/**
 * @author Richard Weinhold
 */

namespace ricwein\PushNotification;

use Composer\CaBundle\CaBundle;
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
    protected ?string $caCertPath = null;

    protected function setCaCertPath(?string $caCertPath): void
    {
        if ($caCertPath !== null) {
            $this->caCertPath = $caCertPath;
        } elseif (class_exists(CaBundle::class)) {
            $this->caCertPath = CaBundle::getSystemCaRootBundlePath();
        }
    }

    protected function applyCACertOptions(array $options): array
    {
        if ($this->caCertPath === null) {
            return $options;
        }

        $caCertPath = realpath($this->caCertPath);
        if ($caCertPath === null || !file_exists($caCertPath) || !is_readable($caCertPath)) {
            throw new RuntimeException("CA not found or not readable for path: $this->caCertPath", 404);
        }

        if (is_dir($caCertPath)) {
            $options[CURLOPT_CAPATH] = $caCertPath;
            return $options;
        }

        $options[CURLOPT_CAINFO] = $caCertPath;
        return $options;
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
