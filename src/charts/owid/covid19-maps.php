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
$_SESSION['owid_maps_filterChart'] = (isset($_SESSION['owid_maps_filterChart'])) ? $_SESSION['owid_maps_filterChart'] : 'owid\maps\deathsPerMillion';

// Affichage du graphique
$class = new $_SESSION['owid_maps_filterChart']($cache);
$class->render();
