<?php
namespace spf;

use tools\dbSingleton;

/**
 * Calcul par jour et par région des chiffres quotidiens (lissé 7 jours) :
 *      - du nombre de tests
 *      - du nombre de positifs
 *      - du taux de positivité
 */
class statsLaboPcrRegCalcLisse7j
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
        $table = 'donnees_labo_pcr_covid19_reg_calc_lisse7j';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "SELECT      jour, reg, T, P
                FROM        donnees_labo_pcr_covid19_reg_calc
                ORDER BY    jour, reg ASC";
        $sql = $this->dbh->query($req);

        $req = "INSERT INTO $tmpTable (jour, reg, T, P, positivite) VALUES ";

        $T1 = [];
        $P1 = [];

        while ($res = $sql->fetch()) {

            $reg = $res->reg;

            $T1[$reg][] = $res->T;
            $P1[$reg][] = $res->P;
            $count = count($T1[$reg]);

            if ($count < 7) {
                continue;
            }

            $T2 = ($T1[$reg][$count-7] + $T1[$reg][$count-6] + $T1[$reg][$count-5] + $T1[$reg][$count-4] + $T1[$reg][$count-3] + $T1[$reg][$count-2] + $T1[$reg][$count-1]) / 7;
            $P2 = ($P1[$reg][$count-7] + $P1[$reg][$count-6] + $P1[$reg][$count-5] + $P1[$reg][$count-4] + $P1[$reg][$count-3] + $P1[$reg][$count-2] + $P1[$reg][$count-1]) / 7;

            $positivite = (empty($T2)) ? 0 : 100 / $T2 * $P2;

            $req .= "('".$res->jour."','".$reg."',".$T2.",".$P2.",".$positivite."),";
        }

        $req = substr($req, 0, -1);
        $sql = $this->dbh->query($req);

        $this->dropTable($table);
        $this->renameTable($tmpTable, $table);
        $this->dropTable($tmpTable);
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
     * @param  string   $tmpTable   Nom table temporaire
     */
    private function createTable($tmpTable)
    {
        $req = "CREATE TABLE `$tmpTable` (
          `id`          int             NOT NULL,
          `jour`        date            NOT NULL,
          `reg`         varchar(2)      COLLATE utf8mb4_unicode_ci NOT NULL,
          `T`           decimal(9,2)    NOT NULL,
          `P`           decimal(9,2)    NOT NULL,
          `positivite`  decimal(5,2)    NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD PRIMARY KEY (`id`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` MODIFY `id` int NOT NULL AUTO_INCREMENT";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD INDEX(`reg`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD INDEX(`jour`)";
        $this->dbh->query($req);
    }
}
