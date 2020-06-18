<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/11/6
 * Time: 3:12 PM
 */

declare(strict_types=1);

namespace Everimg\App;

use Everimg\Lib\Logger;

class Log {
    private static $stdout;
    private static $stderr;
    private static $container = [];
    private static $allows = ['DEBUG', 'INFO', 'WARN', 'ERROR', 'FATAL'];

    public static function init(): bool {
        $threshold = strtoupper(Conf::getEnv('LOG_LEVEL', 'INFO'));

        static::$allows = array_slice(static::$allows, array_search($threshold, static::$allows));

        if (Conf::getEnv("LOG_TO_FILE", false)) {
            static::redirectStream();
        } else {
            self::$stdout = STDOUT;
            self::$stderr = STDERR;
        }

        return true;
    }

    public static function debug(string $msg, ...$args) {
        if (static::checkLevel('DEBUG')) {
            static::getLogger(self::getModuleName())->debug($msg, ...$args);
        }
    }
    public static function info(string $msg, ...$args) {
        if (static::checkLevel('INFO')) {
            static::getLogger(self::getModuleName())->info($msg, ...$args);
        }
    }
    public static function warn(string $msg, ...$args) {
        if (static::checkLevel('ERROR')) {
            static::getLogger(self::getModuleName())->warn($msg, ...$args);
        }
    }
    public static function error(string $msg, ...$args) {
        if (static::checkLevel('ERROR')) {
            static::getLogger(self::getModuleName())->error($msg, ...$args);
        }
    }
    public static function fatal(string $msg, ...$args) {
        if (static::checkLevel('FATAL')) {
            static::getLogger(self::getModuleName())->fatal($msg, ...$args);
        }
    }

    private static function redirectStream() {
        // 不需要重定向至文件
    }
    private static function checkLevel(string $level): bool {
        return in_array($level, static::$allows);
    }
    private static function getLogger($name): Logger {
        if (!isset(self::$container[$name])) {
            self::$container[$name] = new Logger($name, self::$stdout, self::$stderr);
        }

        return self::$container[$name];
    }
    private static function getModuleName(): string {
        return debug_backtrace()[2]['class'];
    }
}
