<?php

// Chargement des classes
include(__DIR__ . '/../../bootstrap.php');

// echo '<pre>';
// print_r($_SESSION);
// echo '<pre>';

// foreach($_SESSION as $k=>$v) {
//     unset($_SESSION[$k]);
// }

if (isset($_SESSION['spf_filterMap'])) {
    $expChart = explode('\\', $_SESSION['spf_filterMap']);
    $className = $expChart[2];
    if (ctype_lower(substr($className, 0, 1))) {
        unset($_SESSION['spf_filterMap']);
    }
}

// Initialisation des filtres
$_SESSION['spf_filterMap']          = (isset($_SESSION['spf_filterMap']))           ? $_SESSION['spf_filterMap']            : 'spf\maps\DeathsPerMillion';
$_SESSION['spf_filterMapInterval']  = (isset($_SESSION['spf_filterMapInterval']))   ? $_SESSION['spf_filterMapInterval']    : 'all';
$_SESSION['spf_filterMapAge']       = (isset($_SESSION['spf_filterMapAge']))        ? $_SESSION['spf_filterMapAge']         : '0';
$_SESSION['spf_filterMapAge2']      = (isset($_SESSION['spf_filterMapAge2']))       ? $_SESSION['spf_filterMapAge2']        : '0';
$_SESSION['spf_filterMapVaccin']    = (isset($_SESSION['spf_filterMapVaccin']))     ? $_SESSION['spf_filterMapVaccin']      : 0;
$_SESSION['spf_filterMapRatio']     = (isset($_SESSION['spf_filterMapRatio']))      ? $_SESSION['spf_filterMapRatio']       : 1;

// Affichage du graphique
$class = new $_SESSION['spf_filterMap']();
$class->render();
