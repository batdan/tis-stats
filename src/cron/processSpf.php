<?php
// Chargement des classes
include ( __DIR__ . '/../vendor/autoload.php' );

if (php_sapi_name() != "cli") {
    die('Hello World !');
}

// Santé publique France
new main\process([

    // Statistique hospitalières quotidiennes
    'statsHpQuotidien',
    'statsHpQuotidienRegCalc',
    'statsHpQuotidienRegCalc7j',

    // Statistique hospitalières cumulées
    'statsHpCumuleAge',
    'statsHpCumuleAgeRegCalc',
    'statsHpCumuleAgeRegCalc7j',

    // Statistique de laboratoire, test PCR
    'statsLaboPcr',
    'statsLaboPcrCalcLisse7j',

    // Statistiques de vaccination
    'statsVaccinationVaccin',
    'statsVaccinationVaccinCalcLisse7j',
    'statsVaccinationAge',
    'statsVaccinationAgeCalcLisse7j',

], 'spf');
