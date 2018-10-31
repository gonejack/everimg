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
    private static $pattern;

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
        static::$pattern = '#<img [^<>]*?src="[^"]+"[^<>]*?>([^<>]*?</img>)?#';
    }
    public static function modifyNoteImages(Note $note):?Note {
        $noteTitle = $note->getTitle();
        $noteContent = $note->getContent()->toEnml();
        $htmlParser = new DOMDocument();

        Log::info("Modifying [%s]", $noteTitle);

        // match images
        if (preg_match_all(static::$pattern, $noteContent, $imgHTMLTags) < 1) {
            Log::debug("Skip note [%s], no images found", $noteTitle);

            return null;
        }

        // modify images
        foreach ($imgHTMLTags[0] as $imgHTMLTag) {
            $fixedTag = str_replace('</img>', '', $imgHTMLTag);
            $fixedTag = mb_convert_encoding($fixedTag, 'HTML-ENTITIES', 'UTF-8');
            $htmlParser->loadHTML($fixedTag);

            foreach ($htmlParser->getElementsByTagName('img') as $imgNode) {
                $imgAttrs = self::parseImageAttrs($imgNode);

                $src = $imgAttrs['src'];
                if (empty($src)) { // invalid media source
                    Log::error("Skip image from note [%s], empty src of img [%s] ", $noteTitle, $imgHTMLTag);
                }
                elseif (strpos($src, 'data') === 0) { // base64 image
                    Log::debug("Skip image from note [%s], base64 image", $noteTitle);
                }
                else {
                    $resource = ActInput::getMediaResource($src);

                    if (is_null($resource)) { // failed building resource
                        Log::error("Skip note [%s], can not build resource [%s]", $noteTitle, $src);

                        return null;
                    }
                    else {
                        $note->addResource($resource);
                        $imgMediaTag = $resource->getEnmlImageTag($imgAttrs);
                        $noteContent = str_replace($imgHTMLTag, $imgMediaTag, $noteContent);

                        Log::info("Add resource [%s]", $src);
                    }
                }
            }
        }

        $note->setContent(new EnmlNoteContent($noteContent));

        return $note;
    }
}