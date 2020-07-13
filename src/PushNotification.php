<?php
/**
 * @author Richard Weinhold
 */

namespace ricwein\PushNotification;

use RuntimeException;

/**
 * PushNotification core
 * @method self setServerUrl(string $url)
 * @method self setServerToken(string $serverToken)
 * @method self setServer(array $server)
 */
class PushNotification
{

    /**
     * @var PushHandler|null
     */
    protected $_handler = null;

    /**
     * @var array
     */
    protected $_devices = [];

    /**
     * @param PushHandler|null $handler
     */
    public function __construct(PushHandler $handler = null)
    {
        if ($handler !== null) {
            $this->setHandler($handler);
        }
    }

    /**
     * @param PushHandler $handler
     * @return self
     */
    public function setHandler(PushHandler $handler): self
    {
        $this->_handler = $handler;
        return $this;
    }

    /**
     * build payload and send via PushHandler to servers
     * @param string $message
     * @param string|null $title
     * @param array $payload
     * @return bool
     */
    public function send(string $message, string $title = null, array $payload = []): bool
    {
        if (!$this->_prepare()) {
            return false;
        }
        return $this->_handler->send($message, $title, $payload, $this->_devices);
    }

    /**
     * send raw payload via PushHandler to servers
     * @param array $payload
     * @return bool
     */
    public function sendRaw(array $payload = []): bool
    {
        if (!$this->_prepare()) {
            return false;
        }
        return $this->_handler->sendRaw($payload, $this->_devices);
    }

    /**
     * prepare PushHandler for sending
     * @return bool
     */
    protected function _prepare(): bool
    {
        if (count($this->_devices) === 0) {
            return false;
        }

        if (!$this->_handler->prepare()) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $device
     * @return self
     */
    public function addDevice($device): self
    {
        $this->_devices = array_merge($this->_devices, (array)$device);
        return $this;
    }

    /**
     * wraps handler-methods
     * @param string $name
     * @param mixed $arguments
     * @return self
     */
    public function __call(string $name, $arguments): self
    {
        if ($this->_handler === null) {
            throw new RuntimeException("Call to {$name}() requires a push-handler to be set, but is null.", 500);
        }

        if (!method_exists($this->_handler, $name)) {
            throw new RuntimeException(sprintf("Unable to call unknown method: %s->%s().", get_class($this->_handler), $name), 500);
        }

        call_user_func_array([$this->_handler, $name], $arguments);
        return $this;
    }
}
