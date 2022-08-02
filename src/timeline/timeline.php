<?php

use timeline\Timeline;

// Chargement des classes
include(__DIR__ . '/../bootstrap.php');

// Affichage de la timeline
$class = new Timeline();
$class->render();
