<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/11/4
 * Time: 9:54 PM
 */

declare(strict_types=1);

class Emoji {
    private static $sina = [];

    public static function getSinaBase64Emoji(string $macro):?string {
        if (empty(static::$sina)) {
            $json = Conf::getResourceContent("sina_emojis.json");

            if ($json) {
                static::$sina = json_decode($json, true);

                Log::debug("Read sina_emojis.json");
            }
            else {
                Log::error("Error loading resource sina_emojis.json");
            }
        }

        return isset(static::$sina[$macro]) ? static::$sina[$macro] : null;
    }
}