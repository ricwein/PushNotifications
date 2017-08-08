<?php
/**
 * @author Richard Weinhold
 */
namespace ricwein\PushNotification;

/**
 * PushNotification core
 */
class PushNotification {

    /**
     * @var PushHandler
     */
    protected $_handler = null;

    /**
     * @var array
     */
    protected $_devices = [];

    /**
     * @param PushHandler|null $handler
     */
    public function __construct(PushHandler $handler = null) {
        if ($handler !== null) {
            $this->setHandler($handler);
        }
    }

    /**
     * @param PushHandler $handler
     *
     * @return self
     */
    public function setHandler(PushHandler $handler): self {
        $this->_handler = $handler;
        return $this;
    }

    /**
     * build payload and send via PushHandler to servers
     *
     * @param string      $message
     * @param string|null $title
     * @param array       $payload
     *
     * @return bool
     */
    public function send(string $message, string $title = null, array $payload = []): bool {
        if (!$this->_prepare()) {
            return false;
        }
        return $this->_handler->send($message, $title, $payload, $this->_devices);
    }

    /**
     * send raw payload via PushHandler to servers
     *
     * @param array $payload
     *
     * @return bool
     */
    public function sendRaw(array $payload = []): bool {
        if (!$this->_prepare()) {
            return false;
        }
        return $this->_handler->sendRaw($payload, $this->_devices);
    }

    /**
     * prepare PushHandler for sending
     *
     * @return bool
     */
    protected function _prepare(): bool {
        if (count($this->_devices) === 0) {
            return false;
        } elseif (!$this->_handler->prepare()) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $device
     *
     * @return self
     */
    public function addDevice($device): self {
        $this->_devices = array_merge($this->_devices, (array) $device);
        return $this;
    }

    /**
     * wrap handler-methods and return $this
     *
     * @param string $name
     * @param mixed  $arguments
     *
     * @throws \Exception
     *
     * @return self
     */
    public function __call(string $name, $arguments): self {
        if (method_exists($this->_handler, $name)) {
            call_user_func_array([$this->_handler, $name], $arguments);
        } else {
            throw new \Exception('unknown call to ' . get_class($this->_handler) . '->' . $name . '()', 500);
        }

        return $this;
    }
}
