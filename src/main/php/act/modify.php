<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 5:18 AM
 */

declare(strict_types=1);

use Evernote\Model\Note;
use Evernote\Model\EnmlNoteContent;

class ActModify {
    private static $imagePattern;
    private static $emojiPattern;

    private static function parseImageAttrs(DOMElement $img) {
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

    public static function init() {
        static::$imagePattern = '#<img [^<>]*?src="[^"]+"[^<>]*?>([^<>]*?</img>)?#';
        static::$emojiPattern = '#\\[[^<>]+?\\]#';
    }
    public static function modifyNoteImages(Note $note):?Note {
        $noteTitle = $note->getTitle();
        $noteContent = $note->getContent()->toEnml();
        $htmlParser = new DOMDocument();
        $changes = 0;

        LogService::info("Modifying [%s]", $noteTitle);

        // modify title
        if (($newTitle = str_replace('[图片]', '', $noteTitle)) !== $noteTitle) {
            $note->setTitle(trim(html_entity_decode($newTitle)));

            LogService::debug("Change title from [%s] => [%s]", $noteTitle, $newTitle);

            $changes += 1;
        }

        // modify images
        if (preg_match_all(static::$imagePattern, $noteContent, $imgHTMLs) < 1) {
            LogService::debug("Skip images modification of note [%s], no images found", $noteTitle);
        }
        else {
            foreach ($imgHTMLs[0] as $imgHTML) {
                $fixedTag = str_replace('</img>', '', $imgHTML);
                $fixedTag = mb_convert_encoding($fixedTag, 'HTML-ENTITIES', 'UTF-8');
                $htmlParser->loadHTML($fixedTag);

                foreach ($htmlParser->getElementsByTagName('img') as $imgNode) {
                    $imgAttrs = self::parseImageAttrs($imgNode);

                    $src = $imgAttrs['src'];
                    if (empty($src)) { // invalid media source
                        LogService::error("Skip image from note [%s], empty src of img [%s] ", $noteTitle, $imgHTML);
                    }
                    elseif (strpos($src, 'data') === 0) { // base64 image
                        LogService::debug("Skip image from note [%s], base64 image", $noteTitle);
                    }
                    else {
                        $resource = ActInput::getMediaResource($src);

                        if (is_null($resource)) { // failed building resource
                            LogService::error("Skip note [%s], can not build resource [%s]", $noteTitle, $src);
                        }
                        else {
                            $note->addResource($resource);
                            $imgMediaTag = $resource->getEnmlImageTag($imgAttrs);
                            $noteContent = str_replace($imgHTML, $imgMediaTag, $noteContent);

                            LogService::info("Add resource [%s]", $src);

                            $changes += 1;
                        }
                    }
                }
            }
        }

        // modify emojis
        if (preg_match_all(static::$emojiPattern, $noteContent, $matches) < 1) {
            LogService::debug("Skip emojis modification of note [%s], no emoji found", $noteTitle);
        }
        else {
            foreach ($matches[0] as $macro) {
                if ($base64 = Emoji::getSinaBase64Emoji($macro)) {
                    $noteContent = str_replace($macro, Kit::getEmojiHTML($macro, $base64), $noteContent);

                    LogService::debug("Replace emoji %s", $macro);

                    $changes += 1;
                }
                else {
                    LogService::warn("Emoji not found %s", $macro);
                }
            }
        }

        $note->setContent(new EnmlNoteContent($noteContent));

        return $changes > 0 ? $note : null;
    }
}