<?php
session_start();

// Sécurité ------------------------------------------------------------------------------------
if (count($_POST) == 0  || !isset($_POST['filterYear1'])) {
    die();
}
// ---------------------------------------------------------------------------------------------

$_SESSION['eurostat_filterYear1'] = $_POST['filterYear1'];

// test ----------------------------------------------------------------------------------------
echo json_encode($_POST);
// ---------------------------------------------------------------------------------------------
