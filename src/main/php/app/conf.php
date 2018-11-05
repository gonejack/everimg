<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/26
 * Time: 3:35 PM
 */

declare(strict_types=1);

class ENV_DEFAULT {
    static $CONF_FILE = './conf/release.ini';
}

class Conf {
    private static $conf;

    public static function init() {
        $file = self::getEnv('CONF_FILE', ENV_DEFAULT::$CONF_FILE);

        if (file_exists($file)) {
            Conf::$conf = parse_ini_file($file);
        }
        else {
            LogService::fatal("Config file [%s] not exist", $file);
        }
    }

    public static function get(string $key, $def=null) {
        $val = @self::$conf[$key];

        return isset($val) ? $val : $def;
    }
    public static function mustGet(string $key) {
        $val = self::get($key);

        if (is_null($val)) {
            LogService::fatal("Required config [$key] not exist");
        }

        return $val;
    }

    public static function getBool(String $key, bool $def):bool {
        return boolval(self::get($key, $def));
    }
    public static function getInt(String $key, int $def):int {
        return intval(self::get($key, $def));
    }
    public static function getEnv(string $key, $def) {
        $val = getenv($key, true) ?: $def;

        LogService::info("Read env %s => %s", $key, var_export($val, true));

        return $val;
    }
    public static function getResourceContent(string $filename):?string {
        $dir = Phar::running() ?: realpath(__DIR__.'/../../../../');

        return @file_get_contents("$dir/src/main/resource/$filename") ?: "";
    }
}

class ConfNotFoundException extends Exception {
    public function __toString() {
        return __CLASS__ . ": [{$this->code}] Config has no key [{$this->message}]\n";
    }
}