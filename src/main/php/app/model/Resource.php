<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/29
 * Time: 4:58 PM
 */

declare(strict_types=1);

class Resource extends \Evernote\Model\Resource {
    public function getEnmlImageTag(array $attrs) {
        $tag = '<en-media %s />';

        $attrs['type'] = $this->mime;
        $attrs['hash'] = $this->hash;

        if ($this->file->getWidth() > 800) {
            $attrs['width'] = 800;

            unset($attrs['height']);
        }

        unset($attrs['src']);

        return sprintf($tag, implode(" ", array_map(function($k, $v) {return "$k=\"$v\"";}, array_keys($attrs), $attrs)));
    }
}