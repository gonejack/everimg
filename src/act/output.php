<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/28
 * Time: 12:24 AM
 */

declare(strict_types=1);

use \Evernote\Model\Note;
use \EDAM\Error\EDAMUserException;

class ActOutput {
    public static function init() {

    }
    public static function uploadModifiedNote(Note $note) {
        try {
            ClientManager::get()->replaceNote($note, $note);

            Log::info("Replaced note [%s]", $note->getTitle());
        }
        catch (EDAMUserException $e) {
            Log::error("Error replacing note [%s, %s] %s", $e->errorCode, $e->parameter, $note->getTitle());
        }
        catch (Exception $e) {
            Log::error("%s", $e->getMessage());
        }
    }
}