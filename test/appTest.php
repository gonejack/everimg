<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 3:28 AM
 */

require "autoload.php";

use PHPUnit\Framework\TestCase;

class AppTest extends TestCase {
    public function testStart() {
        $this->assertEquals(1, 1);
    }
}
