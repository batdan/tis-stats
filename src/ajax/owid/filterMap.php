<?php
session_start();

// Sécurité ------------------------------------------------------------------------------------
if (count($_POST) == 0 || !isset($_POST['filterMap'])) {
    die();
}
// ---------------------------------------------------------------------------------------------

$_SESSION['owid_filterMap'] = $_POST['filterMap'];

// test ----------------------------------------------------------------------------------------
echo json_encode($_POST);
// ---------------------------------------------------------------------------------------------
