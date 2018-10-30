<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/26
 * Time: 3:35 PM
 */

declare(strict_types=1);

class LoggerManager implements ServiceInterface {
    private static $container = [];
    private static $stdout;
    private static $stderr;

    private static function redirect() {

    }

    public static function init():bool {
        if (Conf::getEnv("LOG_TO_FILE", false)) {
            static::redirect();
        }
        else {
            self::$stdout = STDOUT;
            self::$stderr = STDERR;
        }

        static::$container = [];

        return true;
    }
    public static function get($name) {
        if (!isset(self::$container[$name])) {
            self::$container[$name] = new Logger($name, self::$stdout, self::$stderr);
        }

        return self::$container[$name];
    }
}

