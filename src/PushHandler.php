<?php
/**
 * @author Richard Weinhold
 */

namespace ricwein\PushNotification;

use RuntimeException;
use UnexpectedValueException;

/**
 * PushHandler, providing base push operations
 */
abstract class PushHandler
{

    /**
     * @var array
     */
    protected $_server = [
        'token' => '',
        'url' => '',
    ];

    /**
     * @param string|null $serverToken
     * @param string|null $url
     */
    public function __construct(string $serverToken = null, string $url = null)
    {
        if ($serverToken !== null) {
            $this->setServerToken($serverToken);
        }

        if ($url !== null) {
            $this->setServerUrl($url);
        }
    }

    /**
     * @param string $url
     *
     * @return self
     */
    public function setServerUrl(string $url): self
    {
        $this->_server['url'] = $url;
        return $this;
    }

    /**
     * @param string $serverToken
     *
     * @return self
     */
    public function setServerToken(string $serverToken): self
    {
        $this->_server['token'] = $serverToken;
        return $this;
    }

    /**
     * @param array $server
     *
     * @return self
     */
    public function setServer(array $server): self
    {
        $this->_server = array_merge($this->_server, $server);
        return $this;
    }

    /**
     * check and prepare internal configuration for sending
     * @return bool
     * @throws UnexpectedValueException
     */
    public function prepare(): bool
    {
        if (empty($this->_server['token'])) {
            throw new UnexpectedValueException('server token not set', 500);
        }

        if (empty($this->_server['url'])) {
            throw new UnexpectedValueException('server url not set', 500);
        }

        return true;
    }

    /**
     * build default PushNotification and send via PushHandler to servers
     * @param string $message
     * @param string|null $title
     * @param array $payload
     * @param array $devices
     * @return bool
     * @throws UnexpectedValueException|RuntimeException
     *
     */
    abstract public function send(string $message, ?string $title, array $payload, array $devices): bool;

    /**
     * build and send Notification from raw payload
     * @param array $payload
     * @param array $devices
     * @return bool
     * @throws UnexpectedValueException
     * @throws RuntimeException
     */
    abstract public function sendRaw(array $payload, array $devices): bool;
}
