<?php
session_start();

// Sécurité ------------------------------------------------------------------------------------
if (count($_POST) == 0 || !isset($_POST['filterChart'])) {
    die();
}
// ---------------------------------------------------------------------------------------------

$_SESSION['eurostat_filterChart'] = $_POST['filterChart'];

// test ----------------------------------------------------------------------------------------
echo json_encode($_POST);
// ---------------------------------------------------------------------------------------------
