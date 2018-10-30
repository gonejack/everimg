<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/26
 * Time: 3:35 PM
 */

class Conf {
    private static $conf;

    public static function load() {
        Conf::$conf = json_decode(self::getEnv('CONF_FILE', Preset::$CONF_FILE), true);
    }

    /**
     * @param $key
     * @param $def
     * @return mixed
     */
    public static function get($key, $def=null) {
        $val = self::$conf[$key];

        return is_null($val) ? $def : $val;
    }

    /**
     * @param $key
     * @return mixed
     * @throws ConfNotFoundException
     */
    public static function mustGet($key) {
        $val = self::get($key);

        if (is_null($val)) {
            throw new ConfNotFoundException($key);
        }

        return $val;
    }

    public static function getEnv($key, $def) {
        return getenv($key, true) ?: $def;
    }
}

class ConfNotFoundException extends Exception {
    public function __toString() {
        return __CLASS__ . ": [{$this->code}] Config has no key [{$this->message}]\n";
    }
}

class Preset {
    static $CONF_FILE = './conf/dev.json';
}

