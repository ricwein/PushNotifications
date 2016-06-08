 # PushNotification #
 
 ... is a small php-library to wrap Apple, Google and Windows Push-Notifications into a simple syntax.
 
 Examples:
 
 ## Android: ##
  ``` php
use PushNotification\PushNotification;
use PushNotification\Handler\GCMHandler;

$pushHandler = new PushNotification(new GCMHandler());
$pushHandler->setServerToken('ExampleGooglePushToken12345678987654321');
$pushHandler->addDevice('device-token');
$pushHandler->send('message', ['payload'=>'data']);
 ```
 
 ## iOS: ##
  ``` php
use PushNotification\PushNotification;
use PushNotification\Handler\APNSHandler;

$pushHandler = new PushNotification(new APNSHandler());
$pushHandler->setServer([
    'token' => 'path/to/cert.pem',
    'url'   => 'ssl://gateway.sandbox.push.apple.com:2195',
]);
$pushHandler->addDevice('<device-token>');
$pushHandler->send('message', ['payload'=>'data']);
 ```
 
 ## Windows: ##
  ``` php
use PushNotification\PushNotification;
use PushNotification\Handler\WNSHandler;

$pushHandler = new PushNotification(new WNSHandler());
$pushHandler->setServer([
    'token'    => 'wns-push-token',
    'url'      => 'server.url',
]);
$pushHandler->addDevice([
    'clientID' => 'clientSecret',
    'OAuth2-Token',
]);
$pushHandler->send('message', ['payload'=>'data']);
 ```
