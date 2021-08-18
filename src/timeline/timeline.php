<?php
// Chargement des classes
include ( __DIR__ . '/../bootstrap.php' );

// Affichage de la timeline
$class = new \timeline\timeline();
$class->render();
