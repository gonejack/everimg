<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/26
 * Time: 3:30 PM
 */

class App {
    public function main() {
        $this->init();
        $this->work();
    }
    private function init() {
        Conf::load();
    }
    private function work() {
        echo Conf::get("abc", "qq");
    }
}