<?php

namespace spf;

use tools\dbSingleton;
use Exception;

/**
 * Calcul par jour et par région des chiffres quotidiens (lissé 7 jours) :
 *      - du nombre de tests
 *      - du nombre de positifs
 *      - du taux de positivité
 */
class StatsLaboPcrCalcLisse7j
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
        $table = 'donnees_labo_pcr_covid19_calc_lisse7j';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "SELECT      jour, reg, cl_age90, T, P, pop
                FROM        donnees_labo_pcr_covid19
                ORDER BY    jour, reg, cl_age90 ASC";
        $sql = $this->dbh->query($req);

        $req = "INSERT INTO $tmpTable (jour, reg, cl_age90, T, P, pop) VALUES ";

        $T1 = [];
        $P1 = [];

        while ($res = $sql->fetch()) {
            $reg = $res->reg;
            $age = $res->cl_age90;

            $T1[$reg][$age][] = $res->T;
            $P1[$reg][$age][] = $res->P;
            $count = count($T1[$reg][$age]);

            if ($count < 7) {
                continue;
            }

            $T2 = ($T1[$reg][$age][$count-7] + $T1[$reg][$age][$count-6] + $T1[$reg][$age][$count-5] + $T1[$reg][$age][$count-4] + $T1[$reg][$age][$count-3] + $T1[$reg][$age][$count-2] + $T1[$reg][$age][$count-1]) / 7;
            $P2 = ($P1[$reg][$age][$count-7] + $P1[$reg][$age][$count-6] + $P1[$reg][$age][$count-5] + $P1[$reg][$age][$count-4] + $P1[$reg][$age][$count-3] + $P1[$reg][$age][$count-2] + $P1[$reg][$age][$count-1]) / 7;

            $T2 = round($T2, 2);
            $P2 = round($P2, 2);

            // $positivite = (empty($T2)) ? 0 : 100 / $T2 * $P2;

            $req .= "('" . $res->jour . "','" . $reg . "','" . $age . "',";
            $req .= $T2 . "," . $P2 . "," . $res->pop . ")," . chr(10);
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
          `T`           decimal(11,2)   NOT NULL,
          `P`           decimal(11,2)   NOT NULL,
          `pop`         int             NOT NULL
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

        $req = "ALTER TABLE `$table` ADD INDEX(`cl_age90`)";
        $this->dbh->query($req);
    }
}
