<?php
namespace spf;

use tools\dbSingleton;

/**
 * Regroupement par jour et par région des chiffres quotidiens (non lissé) :
 *      - du nombre d'hospitalisations
 *      - du nombre d"entrées en réanimation
 *      - du nombre de décès
 *      - du nombre de retour à domicile
 */
class statsHpQuotidienRegCalc
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
        $table = 'donnees_hp_quotidien_covid19_reg_calc';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "SELECT      jour, reg,
                            SUM(incid_hosp)     AS hosp,
                            SUM(incid_rea)      AS rea,
                            SUM(incid_dc)       AS dc,
                            SUM(incid_rad)      AS rad
                FROM        donnees_hp_quotidien_covid19
                GROUP BY    jour, reg
                ORDER BY    jour, reg ASC";
        $sql = $this->dbh->query($req);

        $req = "INSERT INTO $tmpTable (jour, reg, hosp, rea, dc, rad) VALUES ";

        while ($res = $sql->fetch()) {
            $req .= "('".$res->jour."','".$res->reg."',".$res->hosp.",".$res->rea.",".$res->dc.",".$res->rad.")," . chr(10);
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
    }
}
