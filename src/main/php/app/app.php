<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/26
 * Time: 3:30 PM
 */

declare(strict_types=1);
declare(ticks=1);

class App {
    private static $signal = true;

    private static function init() {
        Conf::init();

        LogService::init();
        ClientService::init();

        Job::init();

        LogService::info("Started");
    }
    private static function work() {
        LogService::info("Working");

        while (self::$signal) {
            sleep(60 * Conf::getInt('update.interval.minutes', 20));

            Job::checkAndModifyNotes();
        }
    }

    public static function start() {
        self::init();
        self::work();
    }
    public static function stop() {
        static::$signal = false;
    }
}
