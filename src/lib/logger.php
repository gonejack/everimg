<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/27
 * Time: 12:38 PM
 */

class Logger {
    private $name;
    private $out;
    private $err;

    public function __construct($name, $out, $err) {
        $this->name = $name;
        $this->out = $out ?: STDOUT;
        $this->err = $err ?: STDERR;
    }

    private function format(string $level, string $msg, ...$args):string {
        $date = date("Y-m-d H:i:s");
        $msg = sprintf($msg, ...$args);

        return "[$date] $level - [$this->name] $msg\n";
    }

    public function debug(string $msg, ...$args):void {
        fwrite($this->out, $this->format('DEBUG', $msg, ...$args));
    }
    public function info(string $msg, ...$args):void {
        fwrite($this->out, $this->format('INFO', $msg, ...$args));
    }
    public function error(string $msg, ...$args):void {
        fwrite($this->err, $this->format('ERROR', $msg, ...$args));
    }
    public function fatal(string $msg, ...$args):void {
        fwrite($this->err, $this->format('FATAL', $msg, ...$args));

        exit(-1);
    }
}