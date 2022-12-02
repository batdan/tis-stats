<?php

namespace spf;

use tools\dbSingleton;
use Exception;

/**
 * Calcul par jour et par région des chiffres quotidiens (lissé 7 jours) :
 *      - du nombre d'hospitalisations
 *      - du nombre d"entrées en réanimation
 *      - du nombre de décès
 *      - du nombre de retour à domicile
 */
class StatsHpCumuleAgeRegCalc7j
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
        $table = 'donnees_hp_cumule_age_covid19_reg_calc_lisse7j';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "SELECT      jour, reg, cl_age90, hosp, rea, dc, rad
                FROM        donnees_hp_cumule_age_covid19_reg_calc
                ORDER BY    jour, reg, cl_age90 ASC";

        $sql = $this->dbh->query($req);

        $req = "INSERT INTO $tmpTable (jour, reg, cl_age90, hosp, rea, dc, rad) VALUES ";

        $hosp   = [];
        $rea    = [];
        $dc     = [];
        $rad    = [];

        while ($res = $sql->fetch()) {
            $reg = $res->reg;
            $age = $res->cl_age90;

            $hosp[$reg][$age][] = $res->hosp;
            $rea[$reg][$age][]  = $res->rea;
            $dc[$reg][$age][]   = $res->dc;
            $rad[$reg][$age][]  = $res->rad;

            $count  = count($hosp[$reg][$age]);

            if ($count < 7) {
                continue;
            }

            $hosp2 = ($hosp[$reg][$age][$count-7] + $hosp[$reg][$age][$count-6] + $hosp[$reg][$age][$count-5] + $hosp[$reg][$age][$count-4] + $hosp[$reg][$age][$count-3] + $hosp[$reg][$age][$count-2] + $hosp[$reg][$age][$count-1]) / 7;
            $rea2  = ($rea[$reg][$age][$count-7]  + $rea[$reg][$age][$count-6]  + $rea[$reg][$age][$count-5]  + $rea[$reg][$age][$count-4]  + $rea[$reg][$age][$count-3]  + $rea[$reg][$age][$count-2]  + $rea[$reg][$age][$count-1])  / 7;
            $dc2   = ($dc[$reg][$age][$count-7]   + $dc[$reg][$age][$count-6]   + $dc[$reg][$age][$count-5]   + $dc[$reg][$age][$count-4]   + $dc[$reg][$age][$count-3]   + $dc[$reg][$age][$count-2]   + $dc[$reg][$age][$count-1])   / 7;
            $rad2  = ($rad[$reg][$age][$count-7]  + $rad[$reg][$age][$count-6]  + $rad[$reg][$age][$count-5]  + $rad[$reg][$age][$count-4]  + $rad[$reg][$age][$count-3]  + $rad[$reg][$age][$count-2]  + $rad[$reg][$age][$count-1])  / 7;

            $req  .= "('" . $res->jour . "','" . $reg . "','" . $age . "'," . $hosp2 . ",";
            $req  .= $rea2 . "," . $dc2 . "," . $rad2 . "),"  .  chr(10);
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
          `id`          int             NOT NULL,
          `jour`        date            NOT NULL,
          `reg`         varchar(2)      COLLATE utf8mb4_unicode_ci NOT NULL,
          `cl_age90`    varchar(2)      COLLATE utf8mb4_unicode_ci NOT NULL,
          `hosp`        decimal(8,2)    NOT NULL,
          `rea`         decimal(8,2)    NOT NULL,
          `dc`          decimal(8,2)    NOT NULL,
          `rad`         decimal(8,2)    NOT NULL
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

        $req = "ALTER TABLE `$table` ADD INDEX(`cl_age90`)";
        $this->dbh->query($req);
    }
}
