<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 3:07 PM
 */

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase {

    public function testInfo() {
        $temp = tmpfile();
        (new Logger('abc', $temp, null))->info("abc");
        fseek($temp, 0);
        $this->assertNotEmpty(fread($temp, 1024));
        fclose($temp);
    }

    public function testError() {
        $temp = tmpfile();
        (new Logger('abc', null, $temp))->error("abc");
        fseek($temp, 0);
        $this->assertNotEmpty(fread($temp, 1024));
        fclose($temp);
    }
}
