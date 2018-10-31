<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/28
 * Time: 12:24 AM
 */

declare(strict_types=1);

use Evernote\Model\Note;
use EDAM\Error\EDAMUserException;
use Thrift\Exception\TTransportException;

class ActOutput {
    public static $networkErrorCodes = [
        TTransportException::NOT_OPEN,
        TTransportException::TIMED_OUT
    ];

    public static function init() {

    }
    public static function uploadModifiedNote(Note $note) {
        $tryTimes = 3;

        while ($tryTimes-- > 0) {
            try {
                Log::debug("Upload note [%s]", $note->getTitle());

                ClientManager::get()->replaceNote($note, $note);

                Log::info("Uploaded note [%s]", $note->getTitle());

                return;
            }
            catch (EDAMUserException $e) {
                Log::error("Error replacing note [%s, %s] %s", $e->errorCode, $e->parameter, $note->getTitle());

                return;
            }
            catch (Exception $e) {
                Log::error("%s", $e->getMessage());

                if (in_array($e->getCode(), static::$networkErrorCodes)) {
                    Log::error("Upload network failure, retrying");
                }
                else {
                    return;
                }
            }
        }

        Log::error("Upload failed with note [%s]", $note->getTitle());
    }
}