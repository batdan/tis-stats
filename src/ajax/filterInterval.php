<?php
session_start();

// Sécurité ------------------------------------------------------------------------------------
if ( count($_POST)==0 || !isset($_POST['filterInterval']) ) {
    die();
}
// ---------------------------------------------------------------------------------------------

$_SESSION['filterInterval'] = $_POST['filterInterval'];

// test ----------------------------------------------------------------------------------------
echo json_encode($_POST);
// ---------------------------------------------------------------------------------------------
