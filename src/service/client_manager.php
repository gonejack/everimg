<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 12:31 PM
 */

declare(strict_types=1);

class ClientManager implements ServiceInterface {
    private static $token;
    private static $sandbox;
    private static $china;
    private static $client;

    private static function buildClient() {
        static::$token = Conf::mustGet('client.token');
        static::$sandbox = Conf::getBool('client.sandbox', true);
        static::$china = Conf::getBool('client.china', false);

        static::$client = new Evernote\Client(static:: $token, static:: $sandbox, null, null, static:: $china);
    }

    public static function get(): \Evernote\Client {
        return static::$client;
    }

    public static function init(): bool {
        static::buildClient();

        return true;
    }
}