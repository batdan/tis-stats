<?php
// Chargement des classes
include(__DIR__ . '/../../bootstrap.php');

$cache = true;
$defaultCountries = 'FR';

// echo '<pre>';
// print_r($_SESSION);
// echo '<pre>';

// foreach ($_SESSION as $k => $v) {
//     unset($_SESSION[$k]);
// }

if (isset($_SESSION['eurostat_filterChart'])) {
    $expChart = explode('\\', $_SESSION['eurostat_filterChart']);
    $className = $expChart[2];
    if (ctype_lower(substr($className, 0, 1))) {
        unset($_SESSION['eurostat_filterChart']);
    }
}

// Initialisation des filtres
$_SESSION['eurostat_filterChart']       = (isset($_SESSION['eurostat_filterChart']))    ? $_SESSION['eurostat_filterChart']     : 'eurostat\charts\DecesStandardises';
$_SESSION['eurostat_filterCountry']     = (isset($_SESSION['eurostat_filterCountry']))  ? $_SESSION['eurostat_filterCountry']   : $defaultCountries;
$_SESSION['eurostat_filterYear1']       = (isset($_SESSION['eurostat_filterYear1']))    ? $_SESSION['eurostat_filterYear1']     : 2021;
$_SESSION['eurostat_filterYear2']       = (isset($_SESSION['eurostat_filterYear2']))    ? $_SESSION['eurostat_filterYear2']     : 2020;
$_SESSION['eurostat_filterSex']         = (isset($_SESSION['eurostat_filterSex']))      ? $_SESSION['eurostat_filterSex']       : 'T';
$_SESSION['eurostat_filterAge']         = (isset($_SESSION['eurostat_filterAge']))      ? $_SESSION['eurostat_filterAge']       : 'TOTAL';
$_SESSION['eurostat_filterUnit']        = (isset($_SESSION['eurostat_filterUnit']))     ? $_SESSION['eurostat_filterUnit']      : 'percent';

// Affichage du graphique
$class = new $_SESSION['eurostat_filterChart']($cache);
$class->render();
