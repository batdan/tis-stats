<?php
namespace spf;

use tools\dbSingleton;

/**
 * Calcul par jour et par région des chiffres quotidiens (lissé 7 jours) :
 *      - du nombre d'hospitalisations
 *      - du nombre d"entrées en réanimation
 *      - du nombre de décès
 *      - du nombre de retour à domicile
 */
class statsHpQuotidienRegCalc7j
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
        $table = 'donnees_hp_quotidien_covid19_reg_calc_lisse7j';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "SELECT      jour, reg, hosp, rea, dc, rad
                FROM        donnees_hp_quotidien_covid19_reg_calc
                ORDER BY    jour, reg ASC";
        $sql = $this->dbh->query($req);

        $req = "INSERT INTO $tmpTable (jour, reg, hosp, rea, dc, rad) VALUES ";

        $hosp   = [];
        $rea    = [];
        $dc     = [];
        $rad    = [];

        while ($res = $sql->fetch()) {

            $reg = $res->reg;

            $hosp[$reg][] = $res->hosp;
            $rea[$reg][]  = $res->rea;
            $dc[$reg][]   = $res->dc;
            $rad[$reg][]  = $res->rad;
            $count  = count($hosp[$reg]);

            if ($count < 7) {
                continue;
            }

            $hosp2 = ($hosp[$reg][$count-7] + $hosp[$reg][$count-6] + $hosp[$reg][$count-5] + $hosp[$reg][$count-4] + $hosp[$reg][$count-3] + $hosp[$reg][$count-2] + $hosp[$reg][$count-1]) / 7;
            $rea2  = ($rea[$reg][$count-7]  + $rea[$reg][$count-6]  + $rea[$reg][$count-5]  + $rea[$reg][$count-4]  + $rea[$reg][$count-3]  + $rea[$reg][$count-2]  + $rea[$reg][$count-1])  / 7;
            $dc2   = ($dc[$reg][$count-7]   + $dc[$reg][$count-6]   + $dc[$reg][$count-5]   + $dc[$reg][$count-4]   + $dc[$reg][$count-3]   + $dc[$reg][$count-2]   + $dc[$reg][$count-1])   / 7;
            $rad2  = ($rad[$reg][$count-7]  + $rad[$reg][$count-6]  + $rad[$reg][$count-5]  + $rad[$reg][$count-4]  + $rad[$reg][$count-3]  + $rad[$reg][$count-2]  + $rad[$reg][$count-1])  / 7;

            $req .= "('".$res->jour."','".$reg."',".$hosp2.",".$rea2.",".$dc2.",".$rad2.")," . chr(10);
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
    }
}
