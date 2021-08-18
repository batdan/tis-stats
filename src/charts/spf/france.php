<?php
// Chargement des classes
include ( __DIR__ . '/../../bootstrap.php' );

$cache = true;

// Initialisation des filtres
$_SESSION['filterChart']        = (isset($_SESSION['filterChart']))      ? $_SESSION['filterChart']      : 'spf\charts\positivite';
$_SESSION['filterRegionId']     = (isset($_SESSION['filterRegionId']))   ? $_SESSION['filterRegionId']   : 0;
$_SESSION['filterRegionName']   = (isset($_SESSION['filterRegionName'])) ? $_SESSION['filterRegionName'] : 'France';
$_SESSION['filterInterval']     = (isset($_SESSION['filterInterval']))   ? $_SESSION['filterInterval']   : 'all';
$_SESSION['filterAge']          = (isset($_SESSION['filterAge']))        ? $_SESSION['filterAge']        : '0';
$_SESSION['filterAge2']         = (isset($_SESSION['filterAge2']))        ? $_SESSION['filterAge2']      : '0';
$_SESSION['filterVaccin']       = (isset($_SESSION['filterVaccin']))     ? $_SESSION['filterVaccin']     : 0;

// Affichage du graphique
$class = new $_SESSION['filterChart']($cache);
$class->render();
