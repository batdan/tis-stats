<?php
namespace spf;

use tools\dbSingleton;

/**
 * Regroupement par jour et par région des chiffres cumulé (non lissé) :
 *      - du nombre d'hospitalisations
 *      - du nombre d"entrées en réanimation
 *      - du nombre de décès
 *      - du nombre de retour à domicile
 */
class statsHpCumuleAgeRegCalc
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
        $table = 'donnees_hp_cumule_age_covid19_reg_calc';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "SELECT      jour, reg, cl_age90,
                            SUM(hosp)     AS sum_hosp,
                            SUM(rea)      AS sum_rea,
                            SUM(dc)       AS sum_dc,
                            SUM(rad)      AS sum_rad

                FROM        donnees_hp_cumule_age_covid19

                GROUP BY    jour, reg, cl_age90
                ORDER BY    jour, reg, cl_age90 ASC";

        $sql = $this->dbh->query($req);

        $req = "INSERT INTO $tmpTable (jour, reg, cl_age90, hosp, rea, dc, rad) VALUES ";

        while ($res = $sql->fetch()) {
            $req .= "('".$res->jour."','".$res->reg."','".$res->cl_age90."',".$res->sum_hosp.",".$res->sum_rea.",".$res->sum_dc.",".$res->sum_rad."),";
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
     * Création de la table temporaire
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
          `hosp`        int             NOT NULL,
          `rea`         int             NOT NULL,
          `dc`          int             NOT NULL,
          `rad`         int             NOT NULL
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
