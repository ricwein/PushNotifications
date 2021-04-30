<?php

namespace ricwein\PushNotification\Exceptions;

use Throwable;

class ResponseReasonException extends ResponseException
{
    public const REASON_UNKNOWN = 'UNKNOWN';

    // APNS
    public const REASON_BAD_DEVICE_TOKEN = 'BadDeviceToken';
    public const REASON_BAD_COLLAPSE_ID = 'BadCollapseId';
    public const REASON_BAD_EXPIRATION_DATE = 'BadExpirationDate';
    public const REASON_BAD_MESSAGE_ID = 'BadMessageId';
    public const REASON_BAD_PRIORITY = 'BadPriority';
    public const REASON_BAD_TOPIC = 'BadTopic';
    public const REASON_DEVICE_TOKEN_NOT_FOR_TOPIC = 'DeviceTokenNotForTopic';
    public const REASON_DUPLICATE_HEADERS = 'DuplicateHeaders';
    public const REASON_IDLE_TIMEOUT = 'IdleTimeout';
    public const REASON_MISSING_DEVICE_TOKEN = 'MissingDeviceToken';
    public const REASON_MISSING_TOPIC = 'MissingTopic';
    public const REASON_PAYLOAD_EMPTY = 'PayloadEmpty';
    public const REASON_TOPIC_DISALLOWED = 'TopicDisallowed';
    public const REASON_BAD_CERTIFICATE = 'BadCertificate';
    public const REASON_BAD_CERTIFICATE_ENVIRONMENT = 'BadCertificateEnvironment';
    public const REASON_EXPIRED_PROVIDER_TOKEN = 'ExpiredProviderToken';
    public const REASON_FORBIDDEN = 'Forbidden';
    public const REASON_INVALID_PROVIDER_TOKEN = 'InvalidProviderToken';
    public const REASON_MISSING_PROVIDER_TOKEN = 'MissingProviderToken';
    public const REASON_BAD_PATH = 'BadPath';
    public const REASON_METHOD_NOT_ALLOWED = 'MethodNotAllowed';
    public const REASON_UNREGISTERED = 'Unregistered';
    public const REASON_PAYLOAD_TOO_LARGE = 'PayloadTooLarge';
    public const REASON_TOO_MANY_PROVIDER_TOKEN_UPDATES = 'TooManyProviderTokenUpdates';
    public const REASON_TOO_MANY_REQUESTS = 'TooManyRequests';
    public const REASON_SERVICE_UNAVAILABLE = 'ServiceUnavailable';
    public const REASON_SHUTDOWN = 'Shutdown';

    // FCM
    public const REASON_NOT_REGISTERED = 'NotRegistered';
    public const REASON_MISSING_REGISTRATION = 'MissingRegistration';
    public const REASON_INVALID_REGISTRATION = 'InvalidRegistration';
    public const REASON_INVALID_PACKAGE_NAME = 'InvalidPackageName';
    public const REASON_MISMATCH_SENDER_ID = 'MismatchSenderId';
    public const REASON_INVALID_PARAMETERS = 'InvalidParameters';
    public const REASON_MESSAGE_TOO_BIG = 'MessageTooBig';
    public const REASON_INVALID_DATA_KEY = 'InvalidDataKey';
    public const REASON_INVALID_TTL = 'InvalidTtl';
    public const REASON_UNAVAILABLE = 'Unavailable';
    public const REASON_DEVICE_MESSAGE_RATE_EXCEEDED = 'DeviceMessageRateExceeded';
    public const REASON_TOPICS_MESSAGE_RATE_EXCEEDED = 'TopicsMessageRateExceeded';
    public const REASON_INVALID_APNS_CREDENTIAL = 'InvalidApnsCredential';

    // FCM + APNS
    public const REASON_INTERNAL_SERVER_ERROR = 'InternalServerError';

    public const GROUP_VALID_REASONS = [
        self::REASON_BAD_DEVICE_TOKEN,
        self::REASON_BAD_COLLAPSE_ID,
        self::REASON_BAD_EXPIRATION_DATE,
        self::REASON_BAD_MESSAGE_ID,
        self::REASON_BAD_PRIORITY,
        self::REASON_BAD_TOPIC,
        self::REASON_DEVICE_TOKEN_NOT_FOR_TOPIC,
        self::REASON_DUPLICATE_HEADERS,
        self::REASON_IDLE_TIMEOUT,
        self::REASON_MISSING_DEVICE_TOKEN,
        self::REASON_MISSING_TOPIC,
        self::REASON_PAYLOAD_EMPTY,
        self::REASON_TOPIC_DISALLOWED,
        self::REASON_BAD_CERTIFICATE,
        self::REASON_BAD_CERTIFICATE_ENVIRONMENT,
        self::REASON_EXPIRED_PROVIDER_TOKEN,
        self::REASON_FORBIDDEN,
        self::REASON_INVALID_PROVIDER_TOKEN,
        self::REASON_MISSING_PROVIDER_TOKEN,
        self::REASON_BAD_PATH,
        self::REASON_METHOD_NOT_ALLOWED,
        self::REASON_UNREGISTERED,
        self::REASON_PAYLOAD_TOO_LARGE,
        self::REASON_TOO_MANY_PROVIDER_TOKEN_UPDATES,
        self::REASON_TOO_MANY_REQUESTS,
        self::REASON_SERVICE_UNAVAILABLE,
        self::REASON_SHUTDOWN,

        self::REASON_NOT_REGISTERED,
        self::REASON_MISSING_REGISTRATION,
        self::REASON_INVALID_REGISTRATION,
        self::REASON_INVALID_PACKAGE_NAME,
        self::REASON_MISMATCH_SENDER_ID,
        self::REASON_INVALID_PARAMETERS,
        self::REASON_MESSAGE_TOO_BIG,
        self::REASON_INVALID_DATA_KEY,
        self::REASON_INVALID_TTL,
        self::REASON_UNAVAILABLE,
        self::REASON_INVALID_APNS_CREDENTIAL,
        self::REASON_DEVICE_MESSAGE_RATE_EXCEEDED,
        self::REASON_TOPICS_MESSAGE_RATE_EXCEEDED,

        self::REASON_INTERNAL_SERVER_ERROR,
    ];

    private string $reason;

    public function __construct(string $reason, $code = 400, Throwable $previous = null)
    {
        if (!in_array($reason, static::GROUP_VALID_REASONS, true)) {
            $reason = static::REASON_UNKNOWN;
        }

        $this->reason = $reason;
        parent::__construct("Request failed with reason: [$code]: $reason", $code, $previous);
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function isInvalidDeviceToken(): bool
    {
        return in_array($this->reason, [
            self::REASON_BAD_DEVICE_TOKEN,
            self::REASON_DEVICE_TOKEN_NOT_FOR_TOPIC,
            self::REASON_NOT_REGISTERED,
            self::REASON_UNREGISTERED,
            self::REASON_INVALID_REGISTRATION,
        ], true);
    }

    public function isRateLimited(): bool
    {
        return in_array($this->reason, [
            self::REASON_DEVICE_MESSAGE_RATE_EXCEEDED,
            self::REASON_TOPICS_MESSAGE_RATE_EXCEEDED,
            self::REASON_TOO_MANY_PROVIDER_TOKEN_UPDATES,
            self::REASON_TOO_MANY_REQUESTS,
        ], true);
    }
}
