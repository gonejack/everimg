<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/30
 * Time: 12:54 AM
 */

require 'vendor/autoload.php';

use Everimg\App\Config;

$sandbox = false;
$china = false;

$oauth_handler = new \Evernote\Auth\OauthHandler($sandbox, false, $china);

$key = Config::get('consumer.key');
$secret = Config::get('consumer.secret');
$callback = 'http://localhost:1234/oauth.php';

try {
    $oauth_data = $oauth_handler->authorize($key, $secret, $callback);

    echo "\nOauth Token : " . $oauth_data['oauth_token'];

    // Now you can use this token to call the api
    $client = new \Evernote\Client($oauth_data['oauth_token']);
} catch (Evernote\Exception\AuthorizationDeniedException $e) {
    //If the user decline the authorization, an exception is thrown.
    echo "Declined";
}
