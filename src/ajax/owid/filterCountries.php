<?php
session_start();

// Sécurité ------------------------------------------------------------------------------------
if ( count($_POST)==0 || !isset($_POST['filterCountries']) ) {
    die();
}
// ---------------------------------------------------------------------------------------------

$_SESSION['owid_filterCountry'] = $_POST['filterCountries'];

// test ----------------------------------------------------------------------------------------
echo json_encode($_POST);
// ---------------------------------------------------------------------------------------------