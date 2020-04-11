<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/26
 * Time: 3:35 PM
 */

declare(strict_types=1);

namespace Everimg\App;

use Exception;
use Phar;

class Conf {
    private static $conf;

    public static function init() {
        $file = self::getEnv('CONF_FILE', './conf/release.ini');

        if (file_exists($file)) {
            Conf::$conf = parse_ini_file($file);
        }
        else {
            Log::fatal("Config file [%s] not exist", $file);
        }
    }

    public static function get(string $key, $def=null) {
        $val = @self::$conf[$key];

        return isset($val) ? $val : $def;
    }
    public static function mustGet(string $key) {
        $val = self::get($key);

        if (is_null($val)) {
            Log::fatal("Required config [$key] not exist");
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

        Log::info("Read env %s => %s", $key, var_export($val, true));

        return $val;
    }
    public static function getResourceContent(string $filename):?string {
        $dir = Phar::running() ?: getcwd();

        return @file_get_contents("$dir/res/$filename") ?: "";
    }
}

class ConfNotFoundException extends Exception {
    public function __toString() {
        return __CLASS__ . ": [{$this->code}] Config has no key [{$this->message}]\n";
    }
}