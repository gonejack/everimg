<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/29
 * Time: 4:58 PM
 */

class Resource extends \Evernote\Model\Resource {
    public function getEnmlImageTag(array $attrs) {
        $tag = '<en-media %s />';

        $attrs['type'] = $this->mime;
        $attrs['hash'] = $this->hash;

        if (!isset($attrs['width']) && !is_null($this->file->getWidth())) {
            $attrs['width'] = $this->file->getWidth();
        }

        if (!isset($attrs['height']) && !is_null($this->file->getHeight())) {
            $attrs['height'] = $this->file->getHeight();
        }

        unset($attrs['src']);

        return sprintf($tag, implode(" ", array_map(function($k, $v) {return "$k=\"$v\"";}, array_keys($attrs), $attrs)));
    }
}