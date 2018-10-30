<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 12:30 PM
 */

declare(strict_types=1);

use \EDAM\NoteStore\NoteMetadata;
use \Evernote\Model\Note;

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
    private static function getLastUpdateTime():int {
        return intval(@file_get_contents(self::$lastUpdateTimeFile)) ?: intval(time() * 1e3);
    }
    private static function saveLastUpdateTime($now) {
        return file_put_contents(self::$lastUpdateTimeFile, strval(intval($now)));
    }

    public static function init() {
        self::$lastUpdateCountFile = Conf::get('deploy.file.last_update_count', './var/last_update_count');
        self::$lastUpdateTimeFile = Conf::get('deploy.file.last_update_time', './var/last_update_time');

        self::$noteFilter = $noteFilter = new \EDAM\NoteStore\NoteFilter([
            'order' => \EDAM\Types\NoteSortOrder::UPDATED,
        ]);

        self::$noteMetaSpec = new \EDAM\NoteStore\NotesMetadataResultSpec([
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
        Log::info("Checking update");

        $metas = [];

        try {
            $client = ClientManager::get();
            $noteStore = $client->getUserNotestore();
            $syncState = $noteStore->getSyncState($client->getToken());

            $lastUpdateCount = self::getLastUpdateCount();
            $lastUpdateTime = self::getLastUpdateTime();

            Log::debug("SyncState updateCount: %s, lastUpdateCount: %s",
                $syncState->updateCount,
                $lastUpdateCount
            );
            Log::debug("SyncState updateTime: %s, lastUpdateTime: %s",
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s', intval($lastUpdateTime/1e3))
            );
            if ($syncState->updateCount > $lastUpdateCount) {
                self::saveLastUpdateCount($syncState->updateCount);
                self::saveLastUpdateTime(time() * 1e3);

                $metaData = $noteStore->findNotesMetadata($client->getToken(), self::$noteFilter, 0, 120, self::$noteMetaSpec);

                foreach ($metaData->notes as $meta) {
                    if ($meta->updated > $lastUpdateTime) {
                        Log::info("Found [%s]", $meta->title);

                        array_push($metas, $meta);
                    }
                }
            }

            Log::info("Fetched %s notes", count($metas));
        }
        catch (Exception $e) {
            Log::error("Check update notes error: %s", $e);
        }

        return $metas;
    }
    public static function getNoteFromMeta(NoteMetadata $meta):?Note {
        try {
            return ClientManager::get()->getNote($meta->guid);
        }
        catch (Exception $e) {
            Log::error("Fetch Note [%s] error: %s", $meta->title, $e);

            return null;
        }
    }
    public static function getMediaResource(string $src):?Resource {
        $resource = null;

        if ($binary = static::downloadBinary($src)) {
            $tmp = tempnam(sys_get_temp_dir(), 'everimg-');

            if (fwrite(fopen($tmp, 'w'), $binary)) {
                $eFile = new \Evernote\File\File($tmp);
                $resource = new Resource($eFile);
            }
            else {
                Log::error("Write disk error with file [%s]", $tmp);
            }

            unlink($tmp);
        }
        else {
            Log::error("Download error with url [%s]", $src);
        }

        return $resource;
    }
    public static function downloadBinary(string $src) {
        Log::debug("Download image [%s]", $src);

        if (strpos($src, '.media.tumblr.com/') !== false && strpos($src, '500.') !== false) {
            $context = stream_context_create([
                "http" => [
                    "method" => "HEAD",
                    "header" => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Safari/605.1.1',
                    ]
                ]
            ]);

            $largeSrc = str_replace('500.', '1280.', $src);
            $headers = get_headers($largeSrc, 0, $context);
            if ($headers && isset($headers[0]) && strpos($headers[0], '200') !== false) {
                Log::debug("Use larger source [%s]", $largeSrc);

                $src = $largeSrc;
            }
        }

        if (strpos($src, '.126.net') !== false || strpos($src, '.127.net') !== false) {
            if (($idx = strpos($src, '?')) !== false) {
                $largeSrc = substr($src, 0, $idx + 1) . 'type=jpg';
                Log::debug("Use larger source [%s]", $largeSrc);

                $src = $largeSrc;
            }
        }

        $context = stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Safari/605.1.1',
                ]
            ]
        ]);

        return @file_get_contents($src, false, $context);
    }
}