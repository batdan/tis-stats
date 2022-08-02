<?php

namespace eurostat\collect;

use tools\dbSingleton;
use tools\config;
use DateTime;
use eurostat\main\Tools;

/**
 * Récupération de la dernière année pour le jeu de données "demo_magec" avec "demo_r_mwk_05"
 */
class DemoMagecAddYear
{
    private $schema = 'tis_stats';

    private $year;              // Année à rattaper
    private $nbDaysWeek1;       // Nombre de jours dans la permière semaine de l'année
    private $nbDaysWeek53;      // Nombre de jours dans la permière semaine de l'année
    
    private $keysAges;
    private $keysCountries;

    public function __construct()
    {
        // Instance PDO
        $this->dbh = dbSingleton::getInstance();

        // Année à rattaper
        $eurostat = config::getConfig('eurostat');
        $this->year = $eurostat['catchYear'];
        
        // Source
        $tableSource = 'eurostat_demo_r_mwk_05';
        
        // Table à compléter
        $tableOpti = 'eurostat_demo_magec_opti';
        
        $this->cleanYear($tableOpti);
        $this->getKeysAges();
        $this->getKeysCountries();
        $this->nbDaysFirstLastWeek();
        $this->processData($tableSource, $tableOpti);
    }

    
    /**
     * On supprime toutes les données de l'année en cours
     * pour éviter les doublons
     */
    private function cleanYear($tableOpti)
    {
        $req = "DELETE FROM $tableOpti WHERE year = :year";
        $sql = $this->dbh->prepare($req);
        $sql->execute([':year' => $this->year]);
    }
    
    
    /**
     * Récupération des tranches d'âge
     */
    private function getKeysAges()
    {
        $this->keysAges = array_keys(Tools::rangeFilterAge());
    }


    /**
     * Récupération des clés ISO-2 des pays à traiter
     */
    private function getKeysCountries()
    {
        $this->keysCountries = array_keys(Tools::getCountries());
    }


    private function processData($tableSource, $tableOpti)
    {
        $geotime = [];
        foreach ($this->keysCountries as $country) {
            $geotime[] = "'" . $country . "'";
        }
        $geotime = implode(',', $geotime);

        // Récupération des données de la dernière années dans la table "eurostat_demo_r_mwk_05"
        $req = "SELECT  sex, age, geotime, year_week, value
                FROM    $tableSource
                WHERE   geotime IN ($geotime)
                AND     age != :age
                AND     year_week LIKE :yearMonth";
        $sql = $this->dbh->prepare($req);
        $sql->execute([
            ':yearMonth' => $this->year . '%',
            ':age'       => 'UNK'
        ]);

        // Requête de vérification pour éviter en entrées en doublon
        $reqChk = " SELECT      id
                    FROM        $tableOpti
                    WHERE       sex     = :sex
                    AND         age     = :age
                    AND         geotime = :geotime
                    AND         year    = :year";
        $sqlChk = $this->dbh->prepare($reqChk);

        // Ajout de la dernière année dans "eurostat_demo_magec"
        $reqUpd = " INSERT INTO $tableOpti
                    (unit, sex, age, geotime, year, value)
                    VALUES
                    (:unit, :sex, :age, :geotime, :year, :value)";
        $sqlUpd = $this->dbh->prepare($reqUpd);

        // Boucle de compilation des données
        $data = [];
        while ($res = $sql->fetch()) {
            $val = $res->value;

            // Ratio au nb de jour de la semaine 1
            if ($res->year_week == $this->year . 'W01') {
                $val = $val / 7 * $this->nbDaysWeek1;
            }

            // Ratio au nb de jour de la semaine 53
            if ($res->year_week == $this->year . 'W53') {
                $val = $val / 7 * $this->nbDaysWeek53;
            }

            $key = $res->sex . '|' . $res->age . '|' . $res->geotime;
            if (!isset($data[$key])) {
                $data[$key]  = $val;
            } else {
                $data[$key] += $val;
            }
        }

        // Boucle d'insertion dans "eurostat_demo_magec"
        foreach ($data as $key => $val) {
            if ($val == 0) {
                continue;
            }

            $exp = explode('|', $key);

            $sex     = $exp[0];
            $age     = $exp[1];
            $geotime = $exp[2];

            $base = [
                ':sex'      => $sex,
                ':age'      => $age,
                ':geotime'  => $geotime,
                ':year'     => $this->year
            ];

            $sqlChk->execute($base);

            if ($sqlChk->rowCount() == 0) {
                $sqlUpd->execute(array_merge($base, [':unit' => 'NR',':value' => $val]));
            }
        }
    }


    /**
     * Calcul automatique du nombre de jour de la 1ère et dernière semaine de l'année à traiter
     * @return [type] [description]
     */
    private function nbDaysFirstLastWeek()
    {
        // nb de jour de la semaine 1
        $d = new DateTime($this->year . '-01-01');
        $this->nbDaysWeek1 = 8 - $d->format('N');

        // nb de jour de la semaine 53
        $d = new DateTime($this->year . '-12-31');
        $this->nbDaysWeek53 = $d->format('N');
    }
}
