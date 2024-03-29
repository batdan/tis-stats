<?php
// Chargement des classes
include(__DIR__ . '/../../bootstrap.php');

$cache = true;
$defaultCountries = ['FRA', 'ISR'];

// echo '<pre>';
// print_r($_SESSION);
// echo '<pre>';

// foreach($_SESSION as $k=>$v) {
//     unset($_SESSION[$k]);
// }

if (isset($_SESSION['owid_filterChart'])) {
    $expChart = explode('\\', $_SESSION['owid_filterChart']);
    $className = $expChart[2];
    if (ctype_lower(substr($className, 0, 1))) {
        unset($_SESSION['owid_filterChart']);
    }
}

// Initialisation des filtres
$_SESSION['owid_filterChart']       = (isset($_SESSION['owid_filterChart']))    ? $_SESSION['owid_filterChart']     : 'owid\charts\NewDeathsSmoothedPerMillion';
$_SESSION['owid_filterCountry']     = (!empty($_SESSION['owid_filterCountry'])) ? $_SESSION['owid_filterCountry']   : $defaultCountries;
$_SESSION['owid_filterInterval']    = (isset($_SESSION['owid_filterInterval'])) ? $_SESSION['owid_filterInterval']  : 'all';

// Affichage du graphique
$class = new $_SESSION['owid_filterChart']($cache);
$class->render();
