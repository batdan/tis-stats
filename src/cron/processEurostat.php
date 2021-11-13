<?php

use main\Process;

// Chargement des classes
include(__DIR__ . '/../vendor/autoload.php');

if (php_sapi_name() != "cli") {
    die('Hello World !');
}

// Santé publique France
new Process([
    // dataset & optimisation
    'DemoPjan',
    'DemoPjanOpti',
    'DemoMagec',
    'DemoMagecOpti',
    'DemoRmwk05',
    'DemoRmwk05Calc8s',
    'DemoMagecAddYear',

], 'eurostat\collect');
