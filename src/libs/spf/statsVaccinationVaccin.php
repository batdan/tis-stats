<?php
namespace spf;

use tools\dbSingleton;
use MongoDB\Driver\Manager;

/**
 * Données relatives aux personnes vaccinées contre la Covid-19 | par vaccins
 *
 *      source : https://www.data.gouv.fr/fr/datasets/donnees-relatives-aux-personnes-vaccinees-contre-la-covid-19-1/
 *      compilation : vacsi-v-reg-2021-08-12-19h05.csv
 *
 *      Jeux de données : https://www.data.gouv.fr/fr/organizations/sante-publique-france/#organization-datasets
 *
 *
 * Documentation :
 *
 * Colonne          Type            Description_FR                                      Exemple
 *
 * jour             string($date)   Date de notification                                2021-01-01      2
 * reg              integer         Region                                              1               0
 * vaccin           integer         Vaccin utilisé                                      0|1|2|3|4       1
 * n_dose1          integer         Nb 1 doses                                          1
 * n_dose2          integer	        Nb 2 doses                                          1
 * n_cum_dose1      integer         Nb cumulé 1 doses                                   1
 * n_cum_dose2      integer         Nb cumulé 2 doses                                   1
 *
 *
 * Vaccins :
 *      0 : Tous vaccins
 *      1 : Pfizer
 *      2 : Moderna
 *      3 : AstraZeneka
 *      4 : Janssen
 */
class statsVaccinationVaccin
{
    /**
     * Instance PDO
     * @var object
     */
    private $dbh;

    /**
     * Url stat
     * @var string
     */
    private $url;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Instance PDO
        $this->dbh = dbSingleton::getInstance();

        // Url de la stat
        $this->url = 'https://www.data.gouv.fr/fr/datasets/r/900da9b0-8987-4ba7-b117-7aea0e53f530';

        $this->checkStat();
    }

    /**
     * Mise à jour des données
     */
    private function checkStat()
    {
        $file  = file($this->url);

        $table = 'donnees_vaccination_vaccin_covid19';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "INSERT INTO $tmpTable (jour, reg, vaccin, n_dose1, n_dose2, n_cum_dose1, n_cum_dose2) VALUES ";

        $i=0;
        foreach ($file as $line) {
            if ($i==0) {
                $i++;
                continue;
            }

            $line = str_replace(chr(10), '', $line);
            $line = explode(';', $line);

            $req .= "('".$line[2]."','".$line[0]."', '".$line[1]."',".$line[3].",".$line[4].",".$line[5].",".$line[6]."),";

            $i++;
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
          `id`          int         NOT NULL,
          `jour`        date        NOT NULL,
          `reg`         varchar(2)  COLLATE utf8mb4_unicode_ci NOT NULL,
          `vaccin`      int         NOT NULL,
          `n_dose1`     int         NOT NULL,
          `n_dose2`     int         NOT NULL,
          `n_cum_dose1` int         NOT NULL,
          `n_cum_dose2` int         NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD PRIMARY KEY (`id`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` MODIFY `id` int NOT NULL AUTO_INCREMENT";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD INDEX(`jour`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD INDEX(`reg`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$tmpTable` ADD INDEX(`vaccin`)";
        $this->dbh->query($req);
    }
}
