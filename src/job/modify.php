<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/30
 * Time: 8:11 PM
 */


class Job {
    public static function init() {
        ActInput::init();
        ActModify::init();
        ActOutput::init();
    }

    public static function checkAndModifyNotes() {
        Log::info("Start [%s]", __FUNCTION__);

        $metas = ActInput::getUpdatedNoteMetas();

        foreach ($metas as $meta) {
            $note = ActInput::getNoteFromMeta($meta);
            if (is_null($note)) {
                continue;
            }

            $modNote = ActModify::modifyNoteImages($note);
            if (is_null($modNote)) {
                continue;
            }

            ActOutput::uploadModifiedNote($modNote);
        }

        Log::info("End [%s]", __FUNCTION__);
    }
}