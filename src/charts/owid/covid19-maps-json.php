<?php

// Chargement des classes
include(__DIR__ . '/../../bootstrap.php');

$cache = true;

// Initialisation des filtres
$_SESSION['owid_filterMap'] = (isset($_SESSION['owid_filterMap'])) ? $_SESSION['owid_filterMap'] : 'owid\maps\DeathsPerMillion';
                                

$class = new $_SESSION['owid_filterMap']($cache);

header('Content-Type: application/json');
echo $class->getData();
