<?php
// Chargement des classes
include ( __DIR__ . '/../../bootstrap.php' );

$cache = true;

// echo '<pre>';
// print_r($_SESSION);
// echo '<pre>';

// foreach($_SESSION as $k=>$v) {
//     unset($_SESSION[$k]);
// }

// Initialisation des filtres
$_SESSION['owid_filterChart']        = (isset($_SESSION['owid_filterChart']))      ? $_SESSION['owid_filterChart']      : 'owid\charts\totalDeathPerMillion';
$_SESSION['owid_filterRegionId']     = (isset($_SESSION['owid_filterRegionId']))   ? $_SESSION['owid_filterRegionId']   : 0;
$_SESSION['owid_filterRegionName']   = (isset($_SESSION['owid_filterRegionName'])) ? $_SESSION['owid_filterRegionName'] : 'France';
$_SESSION['owid_filterInterval']     = (isset($_SESSION['owid_filterInterval']))   ? $_SESSION['owid_filterInterval']   : 'all';
$_SESSION['owid_filterAge']          = (isset($_SESSION['owid_filterAge']))        ? $_SESSION['owid_filterAge']        : '0';
$_SESSION['owid_filterAge2']         = (isset($_SESSION['owid_filterAge2']))       ? $_SESSION['owid_filterAge2']       : '0';
$_SESSION['owid_filterVaccin']       = (isset($_SESSION['owid_filterVaccin']))     ? $_SESSION['owid_filterVaccin']     : 0;

// Affichage du graphique
$class = new $_SESSION['owid_filterChart']($cache);
$class->render();
