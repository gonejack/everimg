<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 12:44 PM
 */

class Log {
    private static $allows = ['DEBUG', 'INFO', 'WARN', 'ERROR', 'FATAL'];

    public static function init() {
        $threadHold = strtoupper(Conf::getEnv('LOG_LEVEL', 'INFO'));

        static::$allows = array_slice(static::$allows, array_search($threadHold, static::$allows));
    }
    private static function check(string $level):bool {
        return in_array($level, static::$allows);
    }
    private static function getModName() {
        return debug_backtrace()[2]['class'];
    }

    public static function debug(string $msg, ...$args) {
        if (static::check('DEBUG')) {
            LoggerManager::get(self::getModName())->debug($msg, ...$args);
        }
    }
    public static function info(string $msg, ...$args) {
        if (static::check('INFO')) {
            LoggerManager::get(self::getModName())->info($msg, ...$args);
        }
    }
    public static function error(string $msg, ...$args) {
        if (static::check('ERROR')) {
            LoggerManager::get(self::getModName())->error($msg, ...$args);
        }
    }
    public static function fatal(string $msg, ...$args) {
        if (static::check('FATAL')) {
            LoggerManager::get(self::getModName())->fatal($msg, ...$args);
        }
    }
}