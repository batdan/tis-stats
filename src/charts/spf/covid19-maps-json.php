<?php
// Chargement des classes
include ( __DIR__ . '/../../bootstrap.php' );

$cache = true;

// Initialisation des filtres
$_SESSION['spf_filterMap']          = (isset($_SESSION['spf_filterMap']))           ? $_SESSION['spf_filterMap']            : 'spf\maps\deathsPerMillion';
$_SESSION['spf_filterMapInterval']  = (isset($_SESSION['spf_filterMapInterval']))   ? $_SESSION['spf_filterMapInterval']    : 'all';
$_SESSION['spf_filterMapAge']       = (isset($_SESSION['spf_filterMapAge']))        ? $_SESSION['spf_filterMapAge']         : '0';
$_SESSION['spf_filterMapAge2']      = (isset($_SESSION['spf_filterMapAge2']))       ? $_SESSION['spf_filterMapAge2']        : '0';
$_SESSION['spf_filterMapVaccin']    = (isset($_SESSION['spf_filterMapVaccin']))     ? $_SESSION['spf_filterMapVaccin']      : 0;
$_SESSION['spf_filterMapRatio']     = (isset($_SESSION['spf_filterMapRatio']))      ? $_SESSION['spf_filterMapRatio']       : 1;

$class = new $_SESSION['spf_filterMap']($cache);

header('Content-Type: application/json');
echo $class->getData();
