<?php
use tools\dbSingleton;

session_start();

include ( __DIR__ . '/../../vendor/autoload.php');

$dbh = dbSingleton::getInstance();

// Sécurité ------------------------------------------------------------------------------------
if ( count($_POST)==0 || !isset($_POST['filterRegion']) ) {
    die();
}
// ---------------------------------------------------------------------------------------------

$_SESSION['spf_filterRegionId'] = $_POST['filterRegion'];

if ($_SESSION['spf_filterRegionId'] == 0) {
    $_SESSION['spf_filterRegionName'] = 'France';
} else {
    $req = "SELECT nccenr FROM geo_reg2018 WHERE region = :region";
    $sql = $dbh->prepare($req);
    $sql->execute([':region' => $_SESSION['spf_filterRegionId']]);

    if ($sql->rowCount()) {
        $res = $sql->fetch();
        $_SESSION['spf_filterRegionName'] = $res->nccenr;
    }
}



// test ----------------------------------------------------------------------------------------
echo json_encode($_POST);
// ---------------------------------------------------------------------------------------------
