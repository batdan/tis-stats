<?php

namespace eurostat\collect;

use tools\dbSingleton;
use eurostat\main\Tools;

/**
 * Récupération et traiement du jeu de données demo_pjan
 */
class DemoMagecOpti
{
    private $schema = 'tis_stats';

    private $datasetName = 'demo_magec';


    public function __construct()
    {
        // Instance PDO
        $this->dbh = dbSingleton::getInstance();

        $table = 'eurostat_' . $this->datasetName . '_opti';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);
        $this->processData($tmpTable);

        $this->dropTable($table);
        $this->renameTable($tmpTable, $table);
    }


    private function processData($tmpTable)
    {
        $keysFilterAge = array_keys(Tools::rangeFilterAge());

        foreach ($keysFilterAge as $key) {
            $addReq = Tools::magecFilterAge2($key);

            $originTable = 'eurostat_' . $this->datasetName;
            $req = "SELECT      unit, sex, geotime, year, SUM(value) AS sumValue
                    FROM        $originTable
                    WHERE       value IS NOT NULL
                    $addReq
                    GROUP BY    year, sex, geotime
                    ORDER BY    year, sex, geotime ASC";

            $sql = $this->dbh->query($req);

            // Création de la requête d'insert
            $req = "INSERT INTO $tmpTable (`unit`, `sex`, `age`, `geotime`, `year`, `value`) VALUES " . chr(10);

            $entries = [];
            while ($res = $sql->fetch()) {
                $addLine = [];
                $addLine[] = "'" . $res->unit . "'";
                $addLine[] = "'" . $res->sex . "'";
                $addLine[] = "'" . $key . "'";
                $addLine[] = "'" . $res->geotime . "'";
                $addLine[] = "'" . $res->year . "'";
                $addLine[] = "'" . $res->sumValue . "'";

                $entries[] = "(" . implode(', ', $addLine) . ")";
            }

            $req .= implode(',', $entries);
            $this->dbh->query($req);
        }
    }


    /**
     * Suppression de la table
     * @param  string $table    Nom table
     */
    private function dropTable($table)
    {
        $schema = $this->schema;

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
     * @param  string   $tmpTable   Nom table temporaire
     */
    private function createTable($tmpTable)
    {
        $this->dropTable($tmpTable);

        $req = "CREATE TABLE `$tmpTable` (
          `id`          int         NOT NULL,
          `unit`        varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
          `sex`         varchar(1)  COLLATE utf8mb4_unicode_ci NOT NULL,
          `age`         varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
          `geotime`     varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
          `year`        int(4)      NOT NULL,
          `value`       int         NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD PRIMARY KEY (`id`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` MODIFY `id` int NOT NULL AUTO_INCREMENT";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD INDEX(`age`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD INDEX(`sex`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD INDEX(`geotime`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD INDEX(`year`)";
        $this->dbh->query($req);
    }
}
