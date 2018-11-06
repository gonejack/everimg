<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/11/6
 * Time: 3:12 PM
 */

declare(strict_types=1);

class Log {
    private static $allows = ['DEBUG', 'INFO', 'WARN', 'ERROR', 'FATAL'];

    public static function init():bool {
        $threadHold = strtoupper(Conf::getEnv('LOG_LEVEL', 'INFO'));

        static::$allows = array_slice(static::$allows, array_search($threadHold, static::$allows));

        return LoggerManager::init();
    }

    public static function debug(string $msg, ...$args) {
        if (static::checkLevel('DEBUG')) {
            LoggerManager::get(self::getModuleName())->debug($msg, ...$args);
        }
    }
    public static function info(string $msg, ...$args) {
        if (static::checkLevel('INFO')) {
            LoggerManager::get(self::getModuleName())->info($msg, ...$args);
        }
    }
    public static function warn(string $msg, ...$args) {
        if (static::checkLevel('ERROR')) {
            LoggerManager::get(self::getModuleName())->warn($msg, ...$args);
        }
    }
    public static function error(string $msg, ...$args) {
        if (static::checkLevel('ERROR')) {
            LoggerManager::get(self::getModuleName())->error($msg, ...$args);
        }
    }
    public static function fatal(string $msg, ...$args) {
        if (static::checkLevel('FATAL')) {
            LoggerManager::get(self::getModuleName())->fatal($msg, ...$args);
        }
    }

    private static function checkLevel(string $level):bool {
        return in_array($level, static::$allows);
    }
    private static function getModuleName() {
        return debug_backtrace()[2]['class'];
    }
}

class LoggerManager {
    private static $container = [];
    private static $stdout;
    private static $stderr;

    public static function init():bool {
        if (Conf::getEnv("LOG_TO_FILE", false)) {
            static::redirectStream();
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
    private static function redirectStream() {

    }
}