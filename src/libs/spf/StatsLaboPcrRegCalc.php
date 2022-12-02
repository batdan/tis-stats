<?php

namespace spf;

use tools\dbSingleton;
use Exception;

/**
 * Regroupement par jour et par région des chiffres quotidiens (non lissé) :
 *      - du nombre de tests
 *      - du nombre de positifs
 *      - du taux de positivité
 */
class StatsLaboPcrRegCalc
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
        $table = 'donnees_labo_pcr_covid19_reg_calc';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "SELECT      jour, reg,
                            SUM(T) AS tests,
                            SUM(P) AS positifs
                FROM        donnees_labo_pcr_covid19
                WHERE       cl_age90 = 0
                GROUP BY    jour, reg
                ORDER BY    jour, reg ASC";
        $sql = $this->dbh->query($req);
        $res = $sql->fetch();

        $positivite = (empty($res->tests)) ? 0 : 100 / $res->tests * $res->positifs;

        $req = "INSERT INTO $tmpTable (jour, reg, T, P, positivite) VALUES ";

        while ($res = $sql->fetch()) {
            $req .= "(  '" . $res->jour . "',
                        '" . $res->reg . "',
                        " . $res->tests . ",
                        " . $res->positifs . ",
                        " . $positivite . "),
                        " . chr(10);
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
        $this->dbh->query($req);
    }


    /**
     * Création de la table
     * @param  string   $table   Nom table temporaire
     */
    private function createTable($table)
    {
        $req = "CREATE TABLE `$table` (
          `id`          int             NOT NULL,
          `jour`        date            NOT NULL,
          `reg`         varchar(2)      COLLATE utf8mb4_unicode_ci NOT NULL,
          `T`           int             NOT NULL,
          `P`           int             NOT NULL,
          `positivite`  decimal(5,2)    NOT NULL
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
