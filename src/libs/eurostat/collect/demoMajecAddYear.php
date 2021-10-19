<?php
namespace collect\eurostat;

use tools\dbSingleton;
use eurostat\main\tools;

/**
 * Récupération de la dernière année pour le jeu de données "demo_magec" avec "demo_r_mwk_05"
 */
class demoMajecAddYear
{
    private $schema = 'tis_stats';

    private $year = 2020;

    private $keysAges;
    private $keysCountries;
    private $magecExistYear;


    public function __construct()
    {
        // Instance PDO
        $this->dbh = dbSingleton::getInstance();

        $this->getKeysAges();
        $this->getKeysCountries();
        $this->checkMagecExistYear();
        $this->processData();

        // echo '<pre>';
        // print_r($this->keysCountries);
        // echo chr(10);
        // echo chr(10);
        // var_dump($this->magecExistYear);
        // echo chr(10);
        // echo chr(10);
        // echo '</pre>';


    }


    private function getKeysAges()
    {
        $this->keysAges = array_keys(tools::rangeFilterAge());
    }


    private function getKeysCountries()
    {
        $this->keysCountries = array_keys(tools::getCountries());
    }


    private function checkMagecExistYear()
    {
        $this->magecExistYear = false;

        $req = "SELECT geotime FROM eurostat_demo_magec WHERE year = :year";
        $sql = $this->dbh->prepare($req);
        $sql->execute([':year' => $this->year]);

        if ($sql->rowCount() >= count($this->keysCountries)) {
            $this->magecExistYear = true;
        }
    }


    private function processData()
    {
        $geotime = [];
        foreach($this->keysCountries as $country) {
            $geotime[] = "'" . $country . "'";
        }
        $geotime = implode(',', $geotime);

        echo chr(10);
        echo chr(10);
        echo 'Test : ' . $geotime;
        echo chr(10);
        echo chr(10);

        return;

        $req = "SELECT  *
                FROM    eurostat_demo_r_mwk_05
                WHERE   geotime IN ($geotime)
                AND     year    = ";

        $reqChk = "SELECT      id
                    FROM        eurostat_demo_magec
                    WHERE       sex     = :sex
                    AND         age     = :age
                    AND         geotime = :geotime
                    AND         year    = :year";
        $sqlChk = $this->dbh->prepare($reqChk);

        $reqUpd = "INSERT INTO eurostat_demo_magec
                    (unit, sex, age, geotime, year, value)
                    VALUES
                    (:unit, :sex, :age, :geotime, :year, :value)";
        $sqlUpd = $this->dbh->prepare($reqUpd);

        foreach ($this->keysCountries as $country) {

        }
    }
}
