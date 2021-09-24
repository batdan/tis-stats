<?php
// Chargement des classes
include ( __DIR__ . '/../../bootstrap.php' );

$test = new eurostat\downloadFile();
$file = $test->getDataset('demo_pjan');

echo chr(10) . chr(10);
print_r($file['file']);
echo chr(10) . chr(10);
