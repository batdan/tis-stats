<?php
// Chargement des classes
include ( __DIR__ . '/../vendor/autoload.php' );

if (php_sapi_name() != "cli") {
    die('Hello World !');
}

// Santé publique France
new main\process([

    // dataset
    'demoPjan',
    'demoMajec',
    'demoRmwk05',

], 'eurostat');
