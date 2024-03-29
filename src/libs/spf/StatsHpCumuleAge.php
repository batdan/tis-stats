<?php

namespace spf;

use tools\dbSingleton;
use Exception;

/**
 * Données hospitalières relatives à l'épidémie de COVID-19 -> cumulé
 *
 *      source : https://www.data.gouv.fr/fr/datasets/donnees-hospitalieres-relatives-a-lepidemie-de-covid-19/
 *      compilation : donnees-hospitalieres-classe-age-covid19-2021-08-03-19h09.csv
 *
 *      Jeux de données : https://www.data.gouv.fr/fr/organizations/sante-publique-france/#organization-datasets
 *
 *
 * Documentation :
 *
 * Colonne          Type            Description_FR                                      Exemple
 *
 * reg              integer         Region                                              1
 * cl_age90         integer         Classe age                                          9
 * jour             string($date)   Date de notification                                18/03/20
 * hosp             integer	        Nombre de personnes actuellement hospitalisées      2
 * rea              integer         Nombre de personnes actuellement
 *                                  en réanimation ou soins intensifs                   0
 * rad              integer	        Nombre cumulé de personnes retournées à domicile    1
 * dc               integer         Nombre cumulé de personnes décédée                  0
 */
class StatsHpCumuleAge
{
    /**
     * Instance PDO
     * @var object
     */
    private $dbh;

    /**
     * Url stat
     * @var string
     */
    private $url;


    /**
     * Constructor
     */
    public function __construct()
    {
        // Instance PDO
        $this->dbh = dbSingleton::getInstance();

        // Url de la stat
        $this->url = 'https://www.data.gouv.fr/fr/datasets/r/08c18e08-6780-452d-9b8c-ae244ad529b3';

        $this->checkStat();
    }


    /**
     * Mise à jour des données
     */
    private function checkStat()
    {
        $file  = file($this->url);

        $table = 'donnees_hp_cumule_age_covid19';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "INSERT INTO $tmpTable (reg, cl_age90, jour, hosp, rea, rad, dc) VALUES ";

        $i = 0;
        foreach ($file as $line) {
            if ($i == 0) {
                $i++;
                continue;
            }

            $line = str_replace(chr(10), '', $line);
            $line = explode(';', $line);

            $reg        = empty($line[0]) ? '' : trim($line[0], '"');
            $cl_age90   = empty($line[1]) ? '' : trim($line[1], '"');
            $jour       = $line[2];
            $hosp       = empty($line[3]) ? 0  : $line[3];
            $rea        = empty($line[4]) ? 0  : $line[4];
            $rad        = empty($line[8]) ? 0  : $line[8];
            $dc         = empty($line[9]) ? 0  : $line[9];

            $req .= "('" . $reg . "', '" . $cl_age90 . "','" . $jour . "'," . $hosp . ",";
            $req .= $rea . "," . $rad . "," . $dc . ")," . chr(10);

            $i++;
        }

        try {
            $req = substr($req, 0, -2);
            $sql = $this->dbh->query($req);

            $this->dropTable($table);
            $this->renameTable($tmpTable, $table);
        } catch (Exception $e) {
            echo chr(10) . $e->getMessage() . chr(10);
        }
    }


    /**
     * Suppression de la table
     * @param  string $table    Nom table
     */
    private function dropTable($table)
    {
        $schema = 'wp_lachainehumaine_stats';

        $req = "SELECT * FROM information_schema.tables WHERE table_schema = '$schema' AND table_name = '$table'";
        $sql = $this->dbh->query($req);

        if ($sql->rowCount()) {
            $req = "DROP TABLE `$table`";
            $sql = $this->dbh->query($req);
        }
    }


    private function renameTable($tmpTable, $table)
    {
        $req = "RENAME TABLE `$tmpTable` TO `$table`";
        $sql = $this->dbh->query($req);
    }


    /**
     * Création de la table
     * @param  string   $table   Nom table temporaire
     */
    private function createTable($table)
    {
        $this->dropTable($table);

        $req = "CREATE TABLE `$table` (
          `id`          int         NOT NULL,
          `reg`         varchar(2)  COLLATE utf8mb4_unicode_ci NOT NULL,
          `cl_age90`    varchar(2)  COLLATE utf8mb4_unicode_ci NOT NULL,
          `jour`        date        NOT NULL,
          `hosp`        int         NOT NULL,
          `rea`         int         NOT NULL,
          `rad`         int         NOT NULL,
          `dc`          int         NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD PRIMARY KEY (`id`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` MODIFY `id` int NOT NULL AUTO_INCREMENT";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`reg`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`jour`)";
        $this->dbh->query($req);
    }
}
