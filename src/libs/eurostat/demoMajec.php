<?php
namespace eurostat;

use tools\dbSingleton;

/**
 * Récupéraiton et traiement du jeu de données demo_pjan
 */
class demoMajec
{
    private $schema = 'tis_stats';

    private $datasetName = 'demo_magec';
    private $dataset;

    private $year;

    public function __construct()
    {
        // Instance PDO
        $this->dbh = dbSingleton::getInstance();

        $this->loadFile();
        $this->processData();
    }


    private function loadFile()
    {
        $class = new downloadFile();
        $this->dataset = $class->getDataset($this->datasetName);
    }

    private function processData()
    {
        $file = $this->dataset['file'];

        $table = 'eurostat_' . $this->datasetName;
        $tmpTable = $table . '_tmp';

        $i=0;
        foreach ($file as $line) {

            // Libelle
            if ($i==0) {
                $this->createTable($line, $tmpTable);

            // Req insert
            } else {
                $this->reqInsert($line, $tmpTable);
            }

            $i++;
        }

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
     * @param  string   $line       Ligne du TSV contenant les libellés
     * @param  string   $tmpTable   Nom table temporaire
     */
    private function createTable($line, $tmpTable)
    {
        $this->dropTable($tmpTable);

        $line = explode(chr(9), $line);

        $req = "CREATE TABLE `$tmpTable` (
          `id`          int         NOT NULL,
          `unit`        varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
          `sex`         varchar(1)  COLLATE utf8mb4_unicode_ci NOT NULL,
          `age`         varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
          `geotime`     varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
          `year`        int(4)      NOT NULL,
          `value`       int         NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->year = [];
        for ($i=1; $i<count($line); $i++) {
            $this->year[] = preg_replace('/[^0-9]/', '', $line[$i]);
        }

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


    /**
     * Création de la requête d'insertion des données
     * @param  string   $line       Ligne du TSV contenant les libellés
     * @param  string   $tmpTable   Nom table temporaire
     */
    private function reqInsert($line, $tmpTable)
    {
        $line = explode(chr(9), $line);
        $cats = explode(',', $line[0]);

        $yearsInLine = [];

        // year / value
        for ($i=1; $i<=count($this->year); $i++) {

            $addLine = [];
            $addLine[] = "'" . trim($cats[0]) . "'";  // unit
            $addLine[] = "'" . trim($cats[1]) . "'";  // sex
            $addLine[] = "'" . trim($cats[2]) . "'";  // age
            $addLine[] = "'" . trim($cats[3]) . "'";  // geotime

            $addLine[] = $this->year[$i-1];

            $val = preg_replace('/[^:0-9]/', '', $line[$i]);
            $val = str_replace(':', 'NULL', $val);
            $val = empty($val) ? 'NULL' : $val;
            $addLine[] = $val;

            $yearsInLine[] = "(" . implode(', ', $addLine) . ")";
        }

        try {
            if (count($this->year)) {
                $req  = "INSERT INTO $tmpTable (`unit`, `sex`, `age`, `geotime`, `year`, `value`) VALUES " . chr(10);
                $req .= implode(',', $yearsInLine);

                $this->dbh->query($req);
            }

        } catch (\Exception $e) {
            echo chr(10) . $e->getMessage() . chr(10);
        }
    }
}
