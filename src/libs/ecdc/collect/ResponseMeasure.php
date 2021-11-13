<?php

namespace ecdc\collect;

use tools\dbSingleton;
use DOMDocument;
use DOMXPath;
use Exception;

class ResponseMeasure
{
    private $src = 'https://www.ecdc.europa.eu/en/publications-data/download-data-response-measures-covid-19';
    
    private $schema = 'tis_stats';

    private $datasetName = 'response_measure';
    private $file;

    public function __construct()
    {
        // Instance PDO
        $this->dbh = dbSingleton::getInstance();

        $this->loadFile();
        $this->countries();
        $this->processData();
    }

    
    private function loadFile()
    {
        $dom = new DOMDocument('1.0', 'utf-8');

        libxml_use_internal_errors(true);
        $dom->loadHTMLFile($this->src, LIBXML_NOBLANKS);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Balise principale master
        $req      = '//a[contains(@href,"response_graphs_data")]';
        $entries  = $xpath->query($req);

        $url = $entries->item(0)->getAttribute('href');

        // Récupération du fichier dans un tableau
        $handle = @fopen($url, "r");
        $this->file = [];
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                $this->file[] = substr($buffer, 0, -1);
            }
            fclose($handle);
        }

        // Suppression de libellés
        unset($this->file[0]);
    }


    private function countries()
    {
        $this->countries = [];

        $req = "SELECT country, iso_3166_1_alpha_2 FROM base_countries_en";
        $sql = $this->dbh->query($req);
        while ($res = $sql->fetch()) {
            $this->countries[strtoupper($res->country)] = $res->iso_3166_1_alpha_2;
        }
    }


    private function processData()
    {
        $table = 'ecdc_' . $this->datasetName;
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $measures = [];

        foreach ($this->file as $line) {
            $expLine = explode(',', $line);

            $country            = trim($expLine[0], '"');
            $iso_3166_1_alpha_2 = isset($this->countries[strtoupper($country)]) ? $this->countries[strtoupper($country)] : 'NULL';
            $response_measure   = trim($expLine[1], '"');
            $date_start         = strstr($expLine[2], 'NA') ? 'NULL' : substr($expLine[2], 0, 10);
            $date_end           = strstr($expLine[3], 'NA') ? 'NULL' : substr($expLine[3], 0, 10);

            $addLine = [];
            $addLine[] = "'" . $country . "'";
            $addLine[] = "'" . $iso_3166_1_alpha_2 . "'";
            $addLine[] = "'" . $response_measure . "'";
            $addLine[] = "'" . $date_start . "'";
            $addLine[] = "'" . $date_end . "'";

            $measures[] = "(" . implode(', ', $addLine) . ")";
        }

        try {
            if (count($this->file)) {
                $req  = "INSERT INTO $tmpTable (country, iso_3166_1_alpha_2, response_measure, date_start, date_end) VALUES " . chr(10);
                $req .= implode(',', $measures);

                // echo chr(10);
                // echo chr(10);
                // print_r($req);
                // echo chr(10);
                // echo chr(10);
                // die;

                $this->dbh->query($req);

                $this->dropTable($table);
                $this->renameTable($tmpTable, $table);
            }
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
          `id`                  int             NOT NULL,
          `country`             varchar(50)     COLLATE utf8mb4_unicode_ci NOT NULL,
          `iso_3166_1_alpha_2`  varchar(2)      COLLATE utf8mb4_unicode_ci NULL,
          `response_measure`    varchar(30)     COLLATE utf8mb4_unicode_ci NOT NULL,
          `date_start`          date            NULL,
          `date_end`            date            NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD PRIMARY KEY (`id`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` MODIFY `id` int NOT NULL AUTO_INCREMENT";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`date_start`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`date_end`)";
        $this->dbh->query($req);
    }
}
