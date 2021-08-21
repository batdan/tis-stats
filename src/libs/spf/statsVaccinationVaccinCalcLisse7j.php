<?php
namespace spf;

use tools\dbSingleton;

/**
 * Données relatives aux personnes vaccinées contre la Covid-19 | par vaccins
 *
 *      source : https://www.data.gouv.fr/fr/datasets/donnees-relatives-aux-personnes-vaccinees-contre-la-covid-19-1/
 *      compilation : vacsi-v-reg-2021-08-12-19h05.csv
 *
 *      Jeux de données : https://www.data.gouv.fr/fr/organizations/sante-publique-france/#organization-datasets
 *
 *
 * Documentation :
 *
 * Colonne          Type            Description_FR                                      Exemple
 *
 * reg              integer         Region                                              1
 * vaccin           integer         Vaccin utilisé                                      0|04|09
 * jour             string($date)   Date de notification                                2021-01-01
 * n_dose1          integer         Nb 1 doses                                          1
 * n_dose2          integer	        Nb 2 doses                                          1
 * n_cum_dose1      integer         Nb cumulé 1 doses                                   1
 * n_cum_dose2      integer         Nb cumulé 2 doses                                   1
 *
 *
 * Vaccins :
 *      0 : Tous vaccins
 *      1 : Pfizer
 *      2 : Moderna
 *      3 : AstraZeneka
 *      4 : Janssen
 */
class statsVaccinationVaccinCalcLisse7j
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
        $table = 'donnees_vaccination_vaccin_covid19_calc_lisse7j';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "SELECT * FROM donnees_vaccination_vaccin_covid19 ORDER BY jour, reg, vaccin ASC";


        $sql = $this->dbh->query($req);

        $req = "INSERT INTO $tmpTable (jour, reg, vaccin, n_dose1, n_dose2, n_cum_dose1, n_cum_dose2) VALUES ";

        $n1  = [];
        $n2  = [];
        $cn1 = [];
        $cn2 = [];

        while ($res = $sql->fetch()) {

            $reg = $res->reg;
            $vax = $res->vaccin;

            $n1[$reg][$vax][]  = $res->n_dose1;
            $n2[$reg][$vax][]  = $res->n_dose2;
            $cn1[$reg][$vax][] = $res->n_cum_dose1;
            $cn2[$reg][$vax][] = $res->n_cum_dose2;

            $count = count($n1[$reg][$vax]);

            if ($count < 7) {
                continue;
            }

            $n1_2   = ($n1[$reg][$vax][$count-7]  + $n1[$reg][$vax][$count-6]  + $n1[$reg][$vax][$count-5]  + $n1[$reg][$vax][$count-4]  + $n1[$reg][$vax][$count-3]  + $n1[$reg][$vax][$count-2]  + $n1[$reg][$vax][$count-1])  / 7;
            $n2_2   = ($n2[$reg][$vax][$count-7]  + $n2[$reg][$vax][$count-6]  + $n2[$reg][$vax][$count-5]  + $n2[$reg][$vax][$count-4]  + $n2[$reg][$vax][$count-3]  + $n2[$reg][$vax][$count-2]  + $n2[$reg][$vax][$count-1])  / 7;
            $cn1_2  = ($cn1[$reg][$vax][$count-7] + $cn1[$reg][$vax][$count-6] + $cn1[$reg][$vax][$count-5] + $cn1[$reg][$vax][$count-4] + $cn1[$reg][$vax][$count-3] + $cn1[$reg][$vax][$count-2] + $cn1[$reg][$vax][$count-1]) / 7;
            $cn2_2  = ($cn2[$reg][$vax][$count-7] + $cn2[$reg][$vax][$count-6] + $cn2[$reg][$vax][$count-5] + $cn2[$reg][$vax][$count-4] + $cn2[$reg][$vax][$count-3] + $cn2[$reg][$vax][$count-2] + $cn2[$reg][$vax][$count-1]) / 7;

            $req .= "('".$res->jour."','".$reg."','".$vax."',".$n1_2.",".$n2_2.",".$cn1_2.",".$cn2_2."),";
        }

        $req = substr($req, 0, -1);
        $sql = $this->dbh->query($req);

        $this->dropTable($table);
        $this->renameTable($tmpTable, $table);
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
            `id`          int         NOT NULL,
            `jour`        date        NOT NULL,
            `reg`         varchar(2)  COLLATE utf8mb4_unicode_ci NOT NULL,
            `vaccin`      int         NOT NULL,
            `n_dose1`     int         NOT NULL,
            `n_dose2`     int         NOT NULL,
            `n_cum_dose1` int         NOT NULL,
            `n_cum_dose2` int         NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD PRIMARY KEY (`id`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` MODIFY `id` int NOT NULL AUTO_INCREMENT";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`jour`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`reg`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`vaccin`)";
        $this->dbh->query($req);
    }
}
