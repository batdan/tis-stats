<?php

use main\Process;

// Chargement des classes
include(__DIR__ . '/../vendor/autoload.php');

if (php_sapi_name() != "cli") {
    die('Hello World !');
}

// Santé publique France
new Process([

    // Statistique hospitalières quotidiennes
    'StatsHpQuotidien',
    'StatsHpQuotidienRegCalc',
    'StatsHpQuotidienRegCalc7j',

    // Statistique hospitalières cumulées
    'StatsHpCumuleAge',
    'StatsHpCumuleAgeRegCalc',
    'StatsHpCumuleAgeRegCalc7j',

    // Statistique de laboratoire, test PCR
    'StatsLaboPcr',
    'StatsLaboPcrCalcLisse7j',

    // Statistiques de vaccination
    'StatsVaccinationVaccin',
    'StatsVaccinationVaccinCalcLisse7j',
    'StatsVaccinationAge',
    'StatsVaccinationAgeCalcLisse7j',

], 'spf');
