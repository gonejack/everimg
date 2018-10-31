<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/31
 * Time: 12:22 PM
 */

use PHPUnit\Framework\TestCase;

class DownloadTest extends TestCase {
    public function testLofter() {
        $res = ActInput::getMediaResource('http://imglf3.nosdn0.126.net/img/dGVYQ3R3R2xtTHJqLzZhOFNLL3h0TXpHRTRmeDRTVFRGYlBLNmFtNkUxNS9SVmJGOVdZZDdRPT0.jpg?type=jpg');

        $this->assertNotNull($res);
    }
}