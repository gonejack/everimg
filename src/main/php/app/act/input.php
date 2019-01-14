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
    private static $sina = [];

    public static $lastUpdateCountFile;
    public static $lastUpdateTimeFile;

    private static $noteFilter;
    private static $noteMetaSpec;

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
        Log::info("Checking update");

        $metas = [];

        try {
            $client = ClientService::get();
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

            Log::info("Fetched %s note metas", count($metas));
        }
        catch (Exception $e) {
            Log::error("Check updated note metas error: %s", $e->getMessage());
        }

        return $metas;
    }
    public static function getNoteFromMeta(NoteMetadata $meta):?Note {
        try {
            Log::debug("Fetch note [%s]", $meta->title);

            $note = ClientService::get()->getNote($meta->guid);

            Log::info("Fetched note [%s]", $meta->title);

            return $note;
        }
        catch (Exception $e) {
            Log::error("Fetch note [%s] error: %s", $meta->title, $e);

            return null;
        }
    }
    public static function getMediaResource(string $src):?Resource {
        $resource = null;

        $better = static::getBetterImageURL($src);
        if ($better !== $src) {
            $src = $better;

            Log::debug("Found better source [%s]", $better);
        }

        if ($content = static::getImage($src)) {
            $tmp = tempnam(sys_get_temp_dir(), 'everimg-');

            if (fwrite(fopen($tmp, 'w'), $content)) {
                $eFile = new File($tmp);
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

    private static function saveLastUpdateCount(int $count) {
        return file_put_contents(self::$lastUpdateCountFile, strval($count));
    }
    private static function saveLastUpdateTime(float $now) {
        return file_put_contents(self::$lastUpdateTimeFile, strval(floor($now)));
    }
    private static function getLastUpdateTime(): float {
        return floor(floatval(@file_get_contents(self::$lastUpdateTimeFile)) ?: time() * 1e3);
    }
    private static function getLastUpdateCount():int {
        return intval(@file_get_contents(self::$lastUpdateCountFile));
    }
    private static function getImage(string $src) {
        Log::debug("Download image [%s]", $src);

        $context = stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Safari/605.1.1',
                ]
            ]
        ]);

        $tryTimes = 3;
        while ($tryTimes-- > 0) {
            $binary = @file_get_contents($src, false, $context) ?: "";
            $expectLen = intval(Net::parseHeaders(@$http_response_header, 'content-length'));
            $realLen = strlen($binary);

            if ($realLen == $expectLen) {
                return $binary;
            }
            else {
                Log::warn("Retry download [%s]", $src);

                sleep(10);
            }
        }

        Log::error("Failed download [%s]", $src);

        return null;
    }
    private static function getBetterImageURL($src):string {
        // tumblr
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

            $better = str_replace('500.', '1280.', $src);
            $response = get_headers($better, 0, $context);

            if ($response && isset($response[0]) && strpos($response[0], '200') !== false) {
                return $better;
            }
        }

        // lofter
        if (strpos($src, '.126.net') !== false || strpos($src, '.127.net') !== false) {
            if (($idx = strpos($src, '?')) !== false) {
                return substr($src, 0, $idx + 1) . 'type=jpg';
            }
        }

        // weibo
        if (preg_match('#^https?:/\w#', $src)) {
            $src = str_replace(':/', '://', $src);
        }
        if (strpos($src, 'sinaimg.cn/woriginal') !== false) {
            $src = str_replace('sinaimg.cn/woriginal', 'sinaimg.cn/large', $src);
        }

        // tuchong
        if (strpos($src, 'photo.tuchong.com') != false) {
            $src = str_replace('/l/', '/f/', $src);
        }

        $src = str_replace(' ', '', $src);

        return $src;
    }
}