<?php

ini_set('memory_limit', '1024M');
date_default_timezone_set('Asia/Shanghai');

require 'vendor/autoload.php';

use Everimg\App\App;
use Everimg\Service\ClientService;

function main() {
    App::add(new ClientService());
    App::boot();
}

main();