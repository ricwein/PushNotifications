# PushNotification

... is a small php-library to wrap Apple (APNS) and Google (FCM) Push-Notifications into a simple syntax.

Examples:

### Android

```php
use ricwein\PushNotification\{PushNotification, Message, Handler};

$message = new Message('message', 'title', ['payload' => 'data']);
$fcm = new Handler\FCM('ExampleGooglePushToken12345678987654321');

$push = new PushNotification(['fcm' => $fcm]);
$push->send($message, ['<device-token>' => 'fcm']);
```

### iOS

> NOTE: The `APNS` Handler uses the *new* apple push servers, which require HTTP2. Therefore, curl with HTTP2 support must be installed.

```php
use ricwein\PushNotification\{PushNotification, Message, Handler, Config};
use Pushok\AuthProvider;

$message = new Message('message', 'title', ['payload' => 'data']);
$apns = new Handler\APNS(AuthProvider\Token::create([
    'key_id' => 'AAAABBBBCC', // The Key ID obtained from Apple developer account
    'team_id' => 'DDDDEEEEFF', // The Team ID obtained from Apple developer account
    'app_bundle_id' => 'com.app.Test', // The bundle ID for app obtained from Apple developer account
    'private_key_path' => __DIR__ . '/private_key.p8', // Path to private key
    'private_key_secret' => null // Private key secret
]), Config::ENV_PRODUCTION);

$push = new PushNotification(['apns' => $apns]);
$push->send($message, ['<device-token>' => 'apns']);
```

### mixed

Sending messages to multiple devices of difference operating systems is also simple:

```php
use ricwein\PushNotification\{PushNotification, Message, Handler, Config};

$message = new Message('message', 'title');
$fcm = new Handler\FCM('ExampleGooglePushToken12345678987654321');
$apns = new Handler\APNS($authToken, Config::ENV_PRODUCTION);

$push = new PushNotification(['apns' => $apns, 'fcm' => $fcm]);
$push->send($message, [
    '<ios-device-token1>' => 'apns',
    '<ios-device-token2>' => 'apns',
    '<android-device-token1>' => 'fcm',
    '<android-device-token2>' => 'fcm',
]);
```

### single message / single handler

For single handler messages it's possible to inline the handler into the device-destination array. The handler is then freed automatically after the message was send.

```php
use ricwein\PushNotification\{PushNotification, Message, Handler};

$result = (new PushNotification)->send(new Message($body,$title), [
    '<device-token>' => new Handler\<APNS/FCM/etc.>(...$config);
]);
```

## usage

This class uses the root-namespace `ricwein\PushNotification`.

### init

The libraries main class is called `PushNotification` and requires an array of available push-handlers for the constructor. It's possible to set an ID as the handlers array key, to allow assigning devices to the handler later on.

Available push-handler are:

- Apple:   `PushNotification\Handler\APNS`
- Google:  `PushNotification\Handler\FCM`

They're all extending `PushNotification\Handler`

### configuration

Since all push-settings are push-handler specific, the settings are directly applied in the handler constructors.

- APNS:
```php
 new APNS(
    \Pushok\AuthProviderInterface $authProvider, /* @see https://github.com/edamov/pushok/blob/master/README.md#getting-started */
    string $environment /* (Config::ENV_PRODUCTION / Config::ENV_DEVELOPMENT / Config::ENV_CUSTOM) */
)
 ```

- FCM:
```php
 new FCM(
    string $token,
    ?string $caCertPath = null,
    string $url = self::FCM_ENDPOINT,
    int $timeout = 10
)
 ```

It's also possible to have multiple push-handlers with different configurations like:

```php
use ricwein\PushNotification\{PushNotification, Message, Handler, Config};

// @see https://github.com/edamov/pushok
$apnsProd = new Handler\APNS($tokenProd, Config::ENV_PRODUCTION);
$apnsDev = new Handler\APNS($tokenDev, Config::ENV_DEVELOPMENT);

$message = new Message('message', 'title');
$push = new PushNotification(['prod' => $apnsProd, 'dev' => $apnsDev]);

$push->send($message, [
    '<ios-device-token1>' => 'prod',
    '<ios-device-token2>' => 'dev',
]);
```

### sending

Sending is either available for a message object or a raw payload.

- A message object is translated into a native push-notification message with body and title for FCM or APNS before sending.
- A raw payload (array) is sent '*as it is*' which might **not** be a good idea, if you want to mix APNS and FCM in one request. 

```php
use ricwein\PushNotification\{Message, Config};

$devices = [...];

$message = new Message('body', 'title');
$message->setSound('test.aiff')->setBadge(2)->setPriority(Config::PRIORITY_NORMAL);
$push->send($message, $devices);

/* OR */

$payload = [...];
$push->sendRaw($payload, $devices);
```

### error handling

The `PushNotification::send()` method returns an `Result` object. This usually contains an array of per device errors. If everything succeeded, the entry is null. You can fetch failed device-messages with:

```php
$result = $push->send($message, [...]);
$errors = $result->getFailed(); 
```

Errors are handled as Exceptions, so it's possible to just throw them. To simply just throw the first error if one occurred, call:

```php
$push->send($message, [...])->throwOnFirstException();
```

***Be aware***: Sometimes other failures than usage-errors occur. APNS and FCM can respond with explicit reasons, which will be handled as `ResponseReasonException`. **It's a good idea to not just throw them (away)**, but handle them other ways. E.g. you might want to delete or update device-tokens which were marked as invalid.

```php
use \ricwein\PushNotification\Exceptions\ResponseReasonException;

foreach($result->getFailed() as $token => $error) {
    if ($error instanceof ResponseReasonException) {
        if ($error->isInvalidDeviceToken()) {
            // $token was invalid
        } elseif ($error->isRateLimited()) {
            // the $token device got too many notifications and is currently rate-limited => better wait some time before sending again.
        }       

    }
}
```
