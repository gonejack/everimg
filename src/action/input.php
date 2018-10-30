<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 12:30 PM
 */

declare(strict_types=1);

class ActionCheck {
    public static function checkNewNotes():array {
        $client = ClientManager::get();
        $store = $client->getUserNotestore();

        return [];
    }
}