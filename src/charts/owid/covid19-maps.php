<?php
// Chargement des classes
include ( __DIR__ . '/../../bootstrap.php' );

// echo '<pre>';
// print_r($_SESSION);
// echo '<pre>';

// foreach($_SESSION as $k=>$v) {
//     unset($_SESSION[$k]);
// }

// Initialisation des filtres
$_SESSION['owid_filterMap'] = (isset($_SESSION['owid_filterMap'])) ? $_SESSION['owid_filterMap'] : 'owid\maps\deathsPerMillion';

// Affichage du graphique
$class = new $_SESSION['owid_filterMap']();
$class->render();
