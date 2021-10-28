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
$_SESSION['spf_filterChart']        = (isset($_SESSION['spf_filterChart']))     ? $_SESSION['spf_filterChart']      : 'spf\charts\quotidienDeces';
$_SESSION['spf_filterRegionId']     = (isset($_SESSION['spf_filterRegionId']))  ? $_SESSION['spf_filterRegionId']   : 0;
$_SESSION['spf_filterRegionName']   = (isset($_SESSION['spf_filterRegionName']))? $_SESSION['spf_filterRegionName'] : 'France';
$_SESSION['spf_filterInterval']     = (isset($_SESSION['spf_filterInterval']))  ? $_SESSION['spf_filterInterval']   : 'all';
$_SESSION['spf_filterAge']          = (isset($_SESSION['spf_filterAge']))       ? $_SESSION['spf_filterAge']        : '0';
$_SESSION['spf_filterAge2']         = (isset($_SESSION['spf_filterAge2']))      ? $_SESSION['spf_filterAge2']       : '0';
$_SESSION['spf_filterVaccin']       = (isset($_SESSION['spf_filterVaccin']))    ? $_SESSION['spf_filterVaccin']     : 0;
$_SESSION['spf_filterUnite']        = (isset($_SESSION['spf_filterUnite']))     ? $_SESSION['spf_filterUnite']      : 'quantity';

// Affichage du graphique
$class = new $_SESSION['spf_filterChart']($cache);
$class->render();
