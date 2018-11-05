<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 12:30 PM
 */

declare(strict_types=1);

use Evernote\File\File;
use Evernote\Model\Note;
use EDAM\NoteStore\NoteMetadata;
use EDAM\Types\NoteSortOrder;
use EDAM\NoteStore\NoteFilter;
use EDAM\NoteStore\NotesMetadataResultSpec;

class ActInput {
    public static $lastUpdateCountFile;
    public static $lastUpdateTimeFile;

    private static $noteFilter;
    private static $noteMetaSpec;

    private static function getLastUpdateCount():int {
        return intval(@file_get_contents(self::$lastUpdateCountFile));
    }
    private static function saveLastUpdateCount(int $count) {
        return file_put_contents(self::$lastUpdateCountFile, strval($count));
    }
    private static function getLastUpdateTime(): float {
        return floor(floatval(@file_get_contents(self::$lastUpdateTimeFile)) ?: time() * 1e3);
    }
    private static function saveLastUpdateTime(float $now) {
        return file_put_contents(self::$lastUpdateTimeFile, strval(floor($now)));
    }

    public static function init() {
        self::$lastUpdateCountFile = Conf::get('deploy.file.last_update_count', './var/last_update_count');
        self::$lastUpdateTimeFile = Conf::get('deploy.file.last_update_time', './var/last_update_time');

        self::$noteFilter = new NoteFilter([
            'order' => NoteSortOrder::UPDATED,
        ]);
        self::$noteMetaSpec = new NotesMetadataResultSpec([
            'includeTitle' => true,
//            'includeCreated' => true,
            'includeUpdated' => true,
//            'includeDeleted' => true,
//            'includeContentLength' => true,
            'includeUpdateSequenceNum' => true,
//            'includeNotebookGuid' => true,
//            'includeTagGuids' => true,
//            'includeAttributes' => true,
//            'includeLargestResourceMime' => true,
//            'includeLargestResourceSize' => true,
        ]);
    }
    public static function getUpdatedNoteMetas():array {
        LogService::info("Checking update");

        $metas = [];

        try {
            $client = ClientService::get();
            $noteStore = $client->getUserNotestore();
            $syncState = $noteStore->getSyncState($client->getToken());

            $lastUpdateCount = self::getLastUpdateCount();
            $lastUpdateTime = self::getLastUpdateTime();

            LogService::debug("SyncState updateCount: %s, lastUpdateCount: %s",
                $syncState->updateCount,
                $lastUpdateCount
            );
            LogService::debug("SyncState updateTime: %s, lastUpdateTime: %s",
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s', intval($lastUpdateTime/1e3))
            );
            if ($syncState->updateCount > $lastUpdateCount) {
                self::saveLastUpdateCount($syncState->updateCount);
                self::saveLastUpdateTime(time() * 1e3);

                $metaData = $noteStore->findNotesMetadata($client->getToken(), self::$noteFilter, 0, 120, self::$noteMetaSpec);

                foreach ($metaData->notes as $meta) {
                    if ($meta->updated > $lastUpdateTime) {
                        LogService::info("Found [%s]", $meta->title);

                        array_push($metas, $meta);
                    }
                }
            }

            LogService::info("Fetched %s note metas", count($metas));
        }
        catch (Exception $e) {
            LogService::error("Check updated note metas error: %s", $e->getMessage());
        }

        return $metas;
    }
    public static function getNoteFromMeta(NoteMetadata $meta):?Note {
        try {
            LogService::debug("Fetch note [%s]", $meta->title);

            $note = ClientService::get()->getNote($meta->guid);

            LogService::info("Fetched note [%s]", $meta->title);

            return $note;
        }
        catch (Exception $e) {
            LogService::error("Fetch note [%s] error: %s", $meta->title, $e);

            return null;
        }
    }
    public static function getMediaResource(string $src):?Resource {
        $resource = null;

        if ($content = Kit::downloadImageBinary($src)) {
            $tmp = tempnam(sys_get_temp_dir(), 'everimg-');

            if (fwrite(fopen($tmp, 'w'), $content)) {
                $eFile = new File($tmp);
                $resource = new Resource($eFile);
            }
            else {
                LogService::error("Write disk error with file [%s]", $tmp);
            }

            unlink($tmp);
        }
        else {
            LogService::error("Download error with url [%s]", $src);
        }

        return $resource;
    }
}