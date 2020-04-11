<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 12:31 PM
 */
declare(strict_types=1);

namespace Everimg\Service;

use DOMDocument;
use DOMElement;
use EDAM\Error\EDAMUserException;
use EDAM\NoteStore\NoteFilter;
use EDAM\NoteStore\NoteMetadata;
use EDAM\NoteStore\NotesMetadataResultSpec;
use EDAM\Types\NoteSortOrder;
use Everimg\App\Conf;
use Everimg\App\Log;
use Everimg\App\Service;
use Everimg\Lib\Net;
use Everimg\Model\Resource;
use Evernote\Client;
use Evernote\File\File;
use Evernote\Model\EnmlNoteContent;
use Evernote\Model\Note;
use Exception;
use Thrift\Exception\TException;
use Thrift\Exception\TTransportException;

class ClientService implements Service {
    private static $token;
    private static $sandbox;
    private static $china;
    private static $client;

    private static $sina = [];

    public static $lastUpdateCountFile;
    public static $lastUpdateTimeFile;

    private static $noteFilter;
    private static $noteMetaSpec;

    private static $imagePattern;
    private static $emojiPattern;

    private static $networkErrorCodes = [
        TTransportException::NOT_OPEN,
        TTransportException::TIMED_OUT
    ];

    public static function init(): bool {
        static::$token = Conf::mustGet('client.token');
        static::$sandbox = Conf::getBool('client.sandbox', true);
        static::$china = Conf::getBool('client.china', false);
        static::$client = new Client(static:: $token, static:: $sandbox, null, null, static:: $china);

        static::$imagePattern = /** @lang regexp */
            '#<img [^<>]*?src="[^"]+"[^<>]*?>([^<>]*?</img>)?#';
        static::$emojiPattern = '#\[[^<>]+?\]#';

        static::$lastUpdateCountFile = Conf::get('deploy.file.last_update_count', './var/last_update_count');
        static::$lastUpdateTimeFile = Conf::get('deploy.file.last_update_time', './var/last_update_time');

        static::$noteFilter = new NoteFilter([
            'order' => NoteSortOrder::UPDATED,
        ]);
        static::$noteMetaSpec = new NotesMetadataResultSpec([
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

        return true;
    }

    public static function start(): void {
        while (true) {
            static::modify();

            sleep(60 * Conf::getInt('update.interval.minutes', 20));
        }
    }

    public static function modify() {
        Log::info("Start [%s]", __METHOD__);

        try {
            $metas = static::getUpdatedNoteMetas();

            foreach ($metas as $meta) {
                $note = static::getNoteFromMeta($meta);
                if (is_null($note)) {
                    continue;
                }

                $modNote = static::modifyNote($note);
                if (is_null($modNote)) {
                    continue;
                }

                static::uploadModifiedNote($modNote);
            }
        } catch (Exception $e) {
            Log::error("Error from [%s]: %s", __METHOD__, $e);
        }

        Log::info("End [%s]", __METHOD__);
    }

    public static function uploadModifiedNote(Note $note) {
        $tryTimes = 3;

        while ($tryTimes-- > 0) {
            try {
                Log::debug("Upload note [%s]", $note->getTitle());

                static::$client->replaceNote($note, $note);

                Log::info("Uploaded note [%s]", $note->getTitle());

                return;
            } catch (EDAMUserException $e) {
                Log::error("Error replacing note [%s, %s] %s", $e->errorCode, $e->parameter, $note->getTitle());

                return;
            } catch (Exception $e) {
                Log::error("%s", $e->getMessage());

                if (in_array($e->getCode(), static::$networkErrorCodes)) {
                    Log::error("Upload network failure, retrying");
                } else {
                    return;
                }
            }
        }

        Log::error("Upload failed with note [%s]", $note->getTitle());
    }

    public static function getUpdatedNoteMetas(): array {
        Log::info("Checking update");

        try {
            $metas = [];

            $noteStore = static::$client->getUserNotestore();
            $syncState = $noteStore->getSyncState(static::$client->getToken());

            $lastUpdateCount = self::getLastUpdateCount();
            $lastUpdateTime = self::getLastUpdateTime();

            Log::debug("SyncState updateCount: %s, lastUpdateCount: %s",
                $syncState->updateCount,
                $lastUpdateCount
            );
            Log::debug("SyncState updateTime: %s, lastUpdateTime: %s",
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s', intval($lastUpdateTime / 1e3))
            );
            if ($syncState->updateCount > $lastUpdateCount) {
                self::saveLastUpdateCount($syncState->updateCount);
                self::saveLastUpdateTime(time() * 1e3);

                $metaData = $noteStore->findNotesMetadata(static::$client->getToken(), self::$noteFilter, 0, 120, self::$noteMetaSpec);

                foreach ($metaData->notes as $meta) {
                    if ($meta->updated > $lastUpdateTime) {
                        Log::info("Found [%s]", $meta->title);

                        array_push($metas, $meta);
                    }
                }
            }

            Log::info("Fetched %s note metas", count($metas));
        } catch (Exception $e) {
            Log::error("Check updated note metas error: code=%d, message=%s", $e->getCode(), $e->getMessage());
        }

        return $metas;
    }

    private static function parseImageAttrs(DOMElement $img): array {
        $keys = [
            'src',
            'align',
            'alt',
            'longdesc',
            'height',
            'width',
            'border',
            'hspace',
            'vspace',
            'usemap',
            'style',
            'title',
            'lang',
            'xml:lang',
            'dir',
        ];
        $values = [];

        foreach ($keys as $attr) {
            if (!empty($val = $img->getAttribute($attr))) {
                $values[$attr] = $val;
            }
        }

        return $values;
    }

    public static function modifyNote(Note $note): ?Note {
        $noteTitle = $note->getTitle();
        $noteContent = $note->getContent()->toEnml();
        $htmlParser = new DOMDocument();
        $changes = 0;

        Log::info("Modifying [%s]", $noteTitle);

        // modify title
        if (($newTitle = str_replace('[图片]', '', $noteTitle)) !== $noteTitle) {
            $note->setTitle(trim(html_entity_decode($newTitle)));

            Log::debug("Change title from [%s] => [%s]", $noteTitle, $newTitle);

            $changes += 1;
        }

        // modify images
        if (preg_match_all(static::$imagePattern, $noteContent, $imgHTMLs) < 1) {
            Log::debug("Skip images modification of note [%s], no images found", $noteTitle);
        } else {
            foreach ($imgHTMLs[0] as $imgHTML) {
                $fixedTag = str_replace('</img>', '', $imgHTML);
                $fixedTag = mb_convert_encoding($fixedTag, 'HTML-ENTITIES', 'UTF-8');
                $htmlParser->loadHTML($fixedTag);

                foreach ($htmlParser->getElementsByTagName('img') as $imgNode) {
                    $imgAttrs = self::parseImageAttrs($imgNode);

                    $src = $imgAttrs['src'];
                    if (empty($src)) { // invalid media source
                        Log::error("Skip image from note [%s], empty src of img [%s] ", $noteTitle, $imgHTML);
                    } elseif (strpos($src, 'data') === 0) { // base64 image
                        Log::debug("Skip image from note [%s], base64 image", $noteTitle);
                    } else {
                        $resource = static::getMediaResource($src);

                        if (is_null($resource)) { // failed building resource
                            Log::error("Skip note [%s], can not build resource [%s]", $noteTitle, $src);
                        } else {
                            /** @var Resource $resource */
                            $note->addResource($resource);
                            $imgMediaTag = $resource->getEnmlImageTag($imgAttrs);
                            $noteContent = str_replace($imgHTML, $imgMediaTag, $noteContent);

                            Log::info("Add resource [%s]", $src);

                            $changes += 1;
                        }
                    }
                }
            }
        }

        // modify emojis
        if (preg_match_all(static::$emojiPattern, $noteContent, $matches) < 1) {
            Log::debug("Skip emojis modification of note [%s], no emoji found", $noteTitle);
        } else {
            foreach ($matches[0] as $macro) {
                if ($base64 = static::getSinaBase64Emoji($macro)) {
                    $noteContent = str_replace($macro, static::newEmojiHTML($macro, $base64), $noteContent);

                    Log::debug("Replace emoji %s", $macro);

                    $changes += 1;
                } else {
                    Log::warn("Emoji not found %s", $macro);
                }
            }
        }

        $note->setContent(new EnmlNoteContent($noteContent));

        return $changes > 0 ? $note : null;
    }

    private static function newEmojiHTML($macro, $src): string {
        $macro = str_replace('[', '', $macro);
        $macro = str_replace(']', '', $macro);

        return "<img src=\"$src\" alt=\"$macro\" />";
    }

    public static function getNoteFromMeta(NoteMetadata $meta): ?Note {
        try {
            Log::debug("Fetch note [%s]", $meta->title);

            $note = static::$client->getNote($meta->guid);

            Log::info("Fetched note [%s]", $meta->title);

            return $note;
        } catch (Exception $e) {
            Log::error("Fetch note [%s] error: %s", $meta->title, $e);

            return null;
        }
    }

    public static function getMediaResource(string $src): ?\Everimg\Model\Resource {
        $resource = null;

        $better = static::getBetterImageURL($src);
        if ($better !== $src) {
            $src = $better;

            Log::debug("Found better source [%s]", $better);
        }

        if ($content = static::getImage($src)) {
            $tmp = tempnam(sys_get_temp_dir(), 'everimg-');

            if (fwrite(fopen($tmp, 'w'), $content)) {
                $size = @getimagesize($tmp);
                if ($size) {
                    $eFile = new File($tmp, $size['mime'], $size[0], $size[1]);
                    $resource = new Resource($eFile);
                } else {
                    Log::error("$tmp is not valid image file");
                }
            } else {
                Log::error("Write disk error with file [%s]", $tmp);
            }

            unlink($tmp);
        } else {
            Log::error("Download error with url [%s]", $src);
        }

        return $resource;
    }

    public static function getSinaBase64Emoji(string $macro): ?string {
        if (empty(static::$sina)) {
            $json = Conf::getResourceContent("sina_emojis.json");

            if ($json) {
                static::$sina = json_decode($json, true);

                Log::debug("Read sina_emojis.json");
            } else {
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

    private static function getLastUpdateCount(): int {
        return intval(@file_get_contents(self::$lastUpdateCountFile));
    }

    private static function getImage(string $src) {
        Log::debug("Download image [%s]", $src);

        $context = stream_context_create([
            "http" => [
                'timeout' => 300,
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
            $status = Net::parseHeaders(@$http_response_header, 'status');
            if (!empty($status) && strpos($status, '4', 0) === 0) {
                Log::warn("Failed with code %s", $status);
                break;
            } else {
                $realLen = strlen($binary);
                $expectLen = intval(Net::parseHeaders(@$http_response_header, 'content-length'));
                if ($realLen > 0 && $realLen == $expectLen) {
                    return $binary;
                } else {
                    Log::warn("Retry download [%s]", $src);
                    sleep(10);
                }
            }
        }

        Log::error("Failed download [%s]", $src);

        return null;
    }

    private static function getBetterImageURL($src): string {
        // tumblr
        if (strpos($src, '.media.tumblr.com/') !== false && strpos($src, '500.') !== false) {
            $context = stream_context_create([
                'timeout' => 300,
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
        if (substr($src, 0, 2) === '//') {
            $src = "http:$src";
        }
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