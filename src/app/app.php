<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/26
 * Time: 3:30 PM
 */

declare(ticks = 1);

class App {
    private static $signal = true;
    
    private static function init() {
        Conf::init();
        Log::init();
        Srv::init();
        Job::init();

        Log::info("Started");
    }
    private static function work() {
        Log::info("Working");

        while (self::$signal) {
            Job::checkAndModifyNotes();

            sleep(60 * Conf::getInt('update.interval.minutes', 20));
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
