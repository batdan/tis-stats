<?php
namespace spf;

use tools\dbSingleton;

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
 * n_cum_dose1      integer         Nb cumulé 1 doses                                   1
 * n_cum_dose2      integer         Nb cumulé 2 doses                                   1
 * couv_dose1       integer         couverture 1 dose                                   1
 * couv_dose2       integer         couverture 2 dose                                   1
 *
 *
 * Ages :
 *      0  : Tous âges
 *      04 : 0-4
 *      09 : 5-9
 *      11 : 10-11
 *      17 : 12-17
 *      24 : 18-24
 *      29 : 25-29
 *      39 : 30-39
 *      49 : 40-49
 *      59 : 50-59
 *      69 : 60-69
 *      74 : 70-74
 *      79 : 75-79
 *      80 : 80 et +
 */
class statsVaccinationAgeCalcLisse7j
{
    /**
     * Instance PDO
     * @var object
     */
    private $dbh;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Instance PDO
        $this->dbh = dbSingleton::getInstance();

        $this->calcStat();
    }

    /**
     * Calcul des données
     */
    private function calcStat()
    {
        $table = 'donnees_vaccination_age_covid19_calc_lisse7j';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "SELECT * FROM donnees_vaccination_age_covid19 ORDER BY jour, reg, clage_vacsi ASC";


        $sql = $this->dbh->query($req);

        $req = "INSERT INTO $tmpTable (jour, reg, clage_vacsi, n_dose1, n_dose2, n_cum_dose1, n_cum_dose2, couv_dose1, couv_dose2) VALUES ";

        $n1  = [];
        $n2  = [];
        $cn1 = [];
        $cn2 = [];
        $cd1 = [];
        $cd2 = [];

        while ($res = $sql->fetch()) {

            $reg = $res->reg;
            $age = $res->clage_vacsi;

            $n1[$reg][$age][]  = $res->n_dose1;
            $n2[$reg][$age][]  = $res->n_dose2;
            $cn1[$reg][$age][] = $res->n_cum_dose1;
            $cn2[$reg][$age][] = $res->n_cum_dose2;
            $cd1[$reg][$age][] = $res->couv_dose1;
            $cd2[$reg][$age][] = $res->couv_dose2;

            $count = count($n1[$reg][$age]);

            if ($count < 7) {
                continue;
            }

            $n1_2   = ($n1[$reg][$age][$count-7]  + $n1[$reg][$age][$count-6]  + $n1[$reg][$age][$count-5]  + $n1[$reg][$age][$count-4]  + $n1[$reg][$age][$count-3]  + $n1[$reg][$age][$count-2]  + $n1[$reg][$age][$count-1])  / 7;
            $n2_2   = ($n2[$reg][$age][$count-7]  + $n2[$reg][$age][$count-6]  + $n2[$reg][$age][$count-5]  + $n2[$reg][$age][$count-4]  + $n2[$reg][$age][$count-3]  + $n2[$reg][$age][$count-2]  + $n2[$reg][$age][$count-1])  / 7;
            $cn1_2  = ($cn1[$reg][$age][$count-7] + $cn1[$reg][$age][$count-6] + $cn1[$reg][$age][$count-5] + $cn1[$reg][$age][$count-4] + $cn1[$reg][$age][$count-3] + $cn1[$reg][$age][$count-2] + $cn1[$reg][$age][$count-1]) / 7;
            $cn2_2  = ($cn2[$reg][$age][$count-7] + $cn2[$reg][$age][$count-6] + $cn2[$reg][$age][$count-5] + $cn2[$reg][$age][$count-4] + $cn2[$reg][$age][$count-3] + $cn2[$reg][$age][$count-2] + $cn2[$reg][$age][$count-1]) / 7;
            $cd1_2  = ($cd1[$reg][$age][$count-7] + $cd1[$reg][$age][$count-6] + $cd1[$reg][$age][$count-5] + $cd1[$reg][$age][$count-4] + $cd1[$reg][$age][$count-3] + $cd1[$reg][$age][$count-2] + $cd1[$reg][$age][$count-1]) / 7;
            $cd2_2  = ($cd2[$reg][$age][$count-7] + $cd2[$reg][$age][$count-6] + $cd2[$reg][$age][$count-5] + $cd2[$reg][$age][$count-4] + $cd2[$reg][$age][$count-3] + $cd2[$reg][$age][$count-2] + $cd2[$reg][$age][$count-1]) / 7;

            $req .= "('".$res->jour."','".$reg."','".$age."',".$n1_2.",".$n2_2.",".$cn1_2.",".$cn2_2.",".$cd1_2.",".$cd2_2.")," . chr(10);
        }

        try {
            $req = substr($req, 0, -2);
            $sql = $this->dbh->query($req);

            $this->dropTable($table);
            $this->renameTable($tmpTable, $table);

        } catch (\Exception $e) {
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
            `n_cum_dose1` int             NOT NULL,
            `n_cum_dose2` int             NOT NULL,
            `couv_dose1`  decimal(5,2)    NOT NULL,
            `couv_dose2`  decimal(5,2)    NOT NULL
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
