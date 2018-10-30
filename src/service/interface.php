<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 4:28 PM
 */

interface ServiceInterface {
    public static function init(): bool;
}

final class Srv {
    public static function init() {
        LoggerManager::init();
        ClientManager::init();
    }
}