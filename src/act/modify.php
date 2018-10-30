<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 5:18 AM
 */

declare(strict_types=1);

use \Evernote\Model\Note;

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
            Log::debug("No images found from [%s], skip note", $noteTitle);

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
                    Log::error("Empty src of img [%s] from note [%s]", $imgHTMLTag, $noteTitle);
                }
                elseif (strpos($src, 'data') === 0) { // base64 image
                    Log::debug("Skip base64 image");
                }
                else {
                    $resource = ActInput::getMediaResource($src);

                    if (is_null($resource)) { // failed building resource
                        Log::error("Error build resource [%s], skip note [%s]", $src, $noteTitle);

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

        $note->setContent(new \Evernote\Model\EnmlNoteContent($noteContent));

        return $note;
    }
}