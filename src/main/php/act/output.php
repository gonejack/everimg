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
                LogService::debug("Upload note [%s]", $note->getTitle());

                ClientService::get()->replaceNote($note, $note);

                LogService::info("Uploaded note [%s]", $note->getTitle());

                return;
            }
            catch (EDAMUserException $e) {
                LogService::error("Error replacing note [%s, %s] %s", $e->errorCode, $e->parameter, $note->getTitle());

                return;
            }
            catch (Exception $e) {
                LogService::error("%s", $e->getMessage());

                if (in_array($e->getCode(), static::$networkErrorCodes)) {
                    LogService::error("Upload network failure, retrying");
                }
                else {
                    return;
                }
            }
        }

        LogService::error("Upload failed with note [%s]", $note->getTitle());
    }
}