<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/11/6
 * Time: 3:24 PM
 */

declare(strict_types=1);

namespace Everimg\App;

interface Service {
    public static function init(): bool;
    public static function start(): void;
}