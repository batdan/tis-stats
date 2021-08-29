<?php
// Chargement des classes
include ( __DIR__ . '/../../bootstrap.php' );

$cache = true;

// Affichage du graphique
$class = new $_SESSION['owid_filterMap']($cache);

header('Content-Type: application/json');
echo $class->getData();
