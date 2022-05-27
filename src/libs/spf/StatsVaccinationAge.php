<?php

namespace spf;

use tools\dbSingleton;
use Exception;

/**
 * Données relatives aux personnes vaccinées contre la Covid-19 | par vaccins
 *
 *      source : https://www.data.gouv.fr/fr/datasets/donnees-relatives-aux-personnes-vaccinees-contre-la-covid-19-1/
 *      compilation : vacsi-a-reg-2021-08-13-19h09.csv
 *
 *      Jeux de données : https://www.data.gouv.fr/fr/organizations/sante-publique-france/#organization-datasets
 *
 *
 * Documentation :
 *
 * Colonne          Type            Description_FR                                      Exemple
 *
 * jour             string($date)   Date de notification                                2021-01-01
 * reg              integer         Region                                              1
 * age              string          Age                                                 0|04|09
 * n_dose1          integer         Nb 1 doses                                          1
 * n_dose2          integer	        Nb 2 doses                                          1
 * n_dose3          integer	        Nb 3 doses                                          1
 * n_cum_dose1      integer         Nb cumulé 1 doses                                   1
 * n_cum_dose2      integer         Nb cumulé 3 doses                                   1
 * n_cum_dose3      integer         Nb cumulé 2 doses                                   1
 * couv_dose1       integer         couverture 1 dose                                   1
 * couv_dose2       integer         couverture 2 dose                                   1
 * couv_dose3       integer         couverture 3 dose                                   1
 *
 *
 * Ages :
 *      0  : Tous âges
 *      4  : 0-4
 *      9  : 5-9
 *      11 : 10-11
 *      17 : 12-17
 *      24 : 18-24
 *      29 : 25-29
 *      39 : 30-39
 *      49 : 40-49
 *      59 : 50-59
 *      64 : 60-64
 *      69 : 65-69
 *      74 : 70-74
 *      79 : 75-79
 *      80 : 80 et +
 */
class StatsVaccinationAge
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
        $this->url = 'https://www.data.gouv.fr/fr/datasets/r/c3ccc72a-a945-494b-b98d-09f48aa25337';

        $this->checkStat();
    }

    /**
     * Mise à jour des données
     */
    private function checkStat()
    {
        $file  = file($this->url);

        $table = 'donnees_vaccination_age_covid19';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "INSERT INTO $tmpTable (
                    jour,
                    reg,
                    clage_vacsi,
                    n_dose1,
                    n_dose2,
                    n_dose3,
                    n_dose4,
                    n_cum_dose1,
                    n_cum_dose2,
                    n_cum_dose3,
                    n_cum_dose4,
                    couv_dose1,
                    couv_dose2,
                    couv_dose3,
                    couv_dose4
                ) VALUES ";

        $i = 0;
        foreach ($file as $line) {
            if ($i == 0) {
                $i++;
                continue;
            }

            $line = substr($line, 0, -1);
            $line = explode(';', $line);

            $jour           = $line[2];
            $reg            = empty($line[0])  ? ''  : trim($line[0], '"');
            $clage_vacsi    = empty($line[1])  ? '0' : str_pad($line[1], 2, '0', STR_PAD_LEFT);
            $n_dose1        = empty($line[3])  ?  0  : $line[3];
            $n_dose2        = empty($line[4])  ?  0  : $line[4];
            $n_dose3        = empty($line[5])  ?  0  : $line[5];
            $n_dose4        = empty($line[6])  ?  0  : $line[6];
            $n_cum_dose1    = empty($line[7])  ?  0  : $line[7];
            $n_cum_dose2    = empty($line[8])  ?  0  : $line[8];
            $n_cum_dose3    = empty($line[9])  ?  0  : $line[9];
            $n_cum_dose4    = empty($line[10])  ?  0  : $line[10];
            $couv_dose1     = empty($line[11])  ?  0  : $line[11];
            $couv_dose2     = empty($line[12]) ?  0  : $line[12];
            $couv_dose3     = empty($line[13]) ?  0  : $line[13];
            $couv_dose4     = empty($line[14]) ?  0  : $line[14];

            $req .= "('" . $jour . "', '" . $reg . "', '" . $clage_vacsi . "', ";
            $req .= $n_dose1 . ", " . $n_dose2 . ", " . $n_dose3 . ", " . $n_dose4 . ", ";
            $req .= $n_cum_dose1 . " , " . $n_cum_dose2 . ", " . $n_cum_dose3 . ", " . $n_cum_dose4 . ", ";
            $req .= $couv_dose1 . ", " . $couv_dose2 . " , " . $couv_dose3 . " , " . $couv_dose4 . ")," . chr(10);

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
        $schema = 'tis_stats';

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
            `id`          int             NOT NULL,
            `jour`        date            NOT NULL,
            `reg`         varchar(2)      COLLATE utf8mb4_unicode_ci NOT NULL,
            `clage_vacsi` varchar(2)      COLLATE utf8mb4_unicode_ci NOT NULL,
            `n_dose1`     int             NOT NULL,
            `n_dose2`     int             NOT NULL,
            `n_dose3`     int             NOT NULL,
            `n_dose4`     int             NOT NULL,
            `n_cum_dose1` int             NOT NULL,
            `n_cum_dose2` int             NOT NULL,
            `n_cum_dose3` int             NOT NULL,
            `n_cum_dose4` int             NOT NULL,
            `couv_dose1`  decimal(5,2)    NOT NULL,
            `couv_dose2`  decimal(5,2)    NOT NULL,
            `couv_dose3`  decimal(5,2)    NOT NULL,
            `couv_dose4`  decimal(5,2)    NOT NULL
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

        $req = "ALTER TABLE `$table` ADD INDEX(`clage_vacsi`)";
        $this->dbh->query($req);
    }
}
