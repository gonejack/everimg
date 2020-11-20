<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 3:52 AM
 */

use Everimg\App\Config;
use PHPUnit\Framework\TestCase;

class ConfTest extends TestCase {
    public function testGet() {
        Config::init();

//        $this->assertEquals(2, Conf::mustGet("mus"));
        $this->assertEquals('./conf/dev.ini', Config::getEnv("CONF_FILE", ""));

        $this->expectException('PHPUnit\Framework\Error\Warning');
        $abc = 0;
        $abc['abc'] = 2;
    }
}
