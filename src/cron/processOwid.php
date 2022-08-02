<?php

use main\Process;

// Chargement des classes
include(__DIR__ . '/../vendor/autoload.php');

if (php_sapi_name() != "cli") {
    die('Hello World !');
}

// Our World in Data
new Process([
    'GetOwidData',
], 'owid');
