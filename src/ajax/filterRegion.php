<?php
use tools\dbSingleton;

session_start();

include ( __DIR__ . '/../vendor/autoload.php');

$dbh = dbSingleton::getInstance();

// Sécurité ------------------------------------------------------------------------------------
if ( count($_POST)==0 || !isset($_POST['filterRegion']) ) {
    die();
}
// ---------------------------------------------------------------------------------------------

$_SESSION['filterRegionId'] = $_POST['filterRegion'];

if ($_SESSION['filterRegionId'] == 0) {
    $_SESSION['filterRegionName'] = 'France';
} else {
    $req = "SELECT nccenr FROM geo_reg2018 WHERE region = :region";
    $sql = $dbh->prepare($req);
    $sql->execute([':region' => $_SESSION['filterRegionId']]);

    if ($sql->rowCount()) {
        $res = $sql->fetch();
        $_SESSION['filterRegionName'] = $res->nccenr;
    }
}



// test ----------------------------------------------------------------------------------------
echo json_encode($_POST);
// ---------------------------------------------------------------------------------------------
