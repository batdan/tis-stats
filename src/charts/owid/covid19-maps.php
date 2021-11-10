<?php

// Chargement des classes
include(__DIR__ . '/../../bootstrap.php');

// echo '<pre>';
// print_r($_SESSION);
// echo '<pre>';

// foreach($_SESSION as $k=>$v) {
//     unset($_SESSION[$k]);
// }

if (isset($_SESSION['owid_filterMap'])) {
    $expChart = explode('\\', $_SESSION['owid_filterMap']);
    $className = $expChart[2];
    if (ctype_lower(substr($className, 0, 1))) {
        unset($_SESSION['owid_filterMap']);
    }
}

// Initialisation des filtres
$_SESSION['owid_filterMap'] = (isset($_SESSION['owid_filterMap'])) ? $_SESSION['owid_filterMap'] : 'owid\maps\DeathsPerMillion';

// Affichage du graphique
$class = new $_SESSION['owid_filterMap']();
$class->render();
