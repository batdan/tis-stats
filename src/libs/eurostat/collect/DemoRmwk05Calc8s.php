<?php

namespace collect\eurostat;

use tools\dbSingleton;

/**
 * Récupération et traiement du jeu de données demo_r_mwk_05 lissé sur 8 semainess
 */
class DemoRmwk05Calc8s
{
    private $schema = 'tis_stats';

    private $datasetName = 'demo_r_mwk_05';
    private $dataset;

    private $yearWeek;

    public function __construct()
    {
        // Instance PDO
        $this->dbh = dbSingleton::getInstance();
        $this->processData();
    }

    private function processData()
    {
        $table = 'eurostat_' . $this->datasetName . '_lisse8s';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);
        $this->lissage($tmpTable);

        $this->dropTable($table);
        $this->renameTable($tmpTable, $table);
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
          `year_week`   varchar(7)  NOT NULL,
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

        $req = "ALTER TABLE `$tmpTable` ADD INDEX(`year_week`)";
        $this->dbh->query($req);
    }


    /**
     * Calcul et insertion des données lissées sur 7 jours
     *
     * @param  string   $tmpTable   Nom table temporaire
     */
    private function lissage($tmpTable)
    {
        $req = "SELECT      *
                FROM        eurostat_demo_r_mwk_05
                ORDER BY    year_week, geotime, age, sex";
        $sql = $this->dbh->query($req);

        $req  = "INSERT INTO $tmpTable (unit, sex, age, geotime, year_week, value) VALUES ";
        $req2 = $req;

        $value = [];
        $i = 0;

        while ($res = $sql->fetch()) {
            $year_week  = $res->year_week;
            $geotime    = $res->geotime;
            $sex        = $res->sex;
            $age        = $res->age;

            $value[$geotime][$sex][$age][] = $res->value;

            $count  = count($value[$geotime][$sex][$age]);
            if ($count < 8) {
                continue;
            }

            $value2 = ($value[$geotime][$sex][$age][$count - 8]
                    +  $value[$geotime][$sex][$age][$count - 7]
                    +  $value[$geotime][$sex][$age][$count - 6]
                    +  $value[$geotime][$sex][$age][$count - 5]
                    +  $value[$geotime][$sex][$age][$count - 4]
                    +  $value[$geotime][$sex][$age][$count - 3]
                    +  $value[$geotime][$sex][$age][$count - 2]
                    +  $value[$geotime][$sex][$age][$count - 1]) / 8;
            $value2 = round($value2);

            $req2 .= "('NR', 
                        '" . $sex . "',
                        '" . $age . "',
                        '" . $geotime . "',
                        '" . $year_week . "',
                        " . $value2 . "),";

            $i++;

            if ($i % 20000 == 0) {
                $req2 = substr($req2, 0, -1);
                $sql2 = $this->dbh->query($req2);

                $req2 = $req;
                $i = 0;
            }
        }
    }
}
