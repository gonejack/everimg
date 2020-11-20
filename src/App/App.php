<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/26
 * Time: 3:30 PM
 */

declare(strict_types=1);
declare(ticks=1);

namespace Everimg\App;

class App {
    private static $services = [];

    public static function add(Service $service) {
        array_push(self::$services, $service);
    }

    private static function init() {
        Config::init();
        Log::init();

        foreach (static::$services as $service) {
            $service->init();
        }
    }

    public static function boot() {
        self::init();

        Log::info("Working");
        foreach (static::$services as $service) {
            $service->start();
        }
    }
}
