<?php
namespace spf;

use tools\dbSingleton;

/**
 * Données hospitalières relatives à l'épidémie de COVID-19 -> quotidien
 *
 *      source : https://www.data.gouv.fr/fr/datasets/donnees-hospitalieres-relatives-a-lepidemie-de-covid-19/
 *      compilation : donnees-hospitalieres-nouveaux-covid19-2021-08-02-19h09.csv
 *
 *      Jeux de données : https://www.data.gouv.fr/fr/organizations/sante-publique-france/#organization-datasets
 *
 *
 * Documentation :
 *
 * Colonne  Type            Description_FR                                      Exemple
 *
 * dep      integer         Département                                         1
 * sexe     integer         Sexe                                                0
 * jour     string($date)   Date de notification                                18/03/2020
 * hosp     integer         Nombre de personnes actuellement hospitalisées      2
 * rea      integer         Nombre de personnes actuellement
 *                          en réanimation ou soins intensifs                   0
 * rad      integer         Nombre cumulé de personnes retournées à domicile    1
 * dc       integer         Nombre cumulé de personnes décédées à l'hôpital     0
 */
class statsHpQuotidien
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
        $this->url = 'https://www.data.gouv.fr/fr/datasets/r/6fadff46-9efd-4c53-942a-54aca783c30c';

        $this->checkStat();
    }

    /**
     * Mise à jour des données
     */
    private function checkStat()
    {
        $file  = file($this->url);

        $table = 'donnees_hp_quotidien_covid19';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "INSERT INTO $tmpTable (dep, jour, incid_hosp, incid_rea, incid_dc, incid_rad) VALUES ";

        $i=0;
        foreach ($file as $line) {
            if ($i==0) {
                $i++;
                continue;
            }

            $line = str_replace(chr(10), '', $line);
            $line = explode(';', $line);

            $dep        = empty($line[0]) ? '' : trim($line[0],'"');
            $jour       = $line[1];
            $incid_hosp = empty($line[2]) ? 0  : $line[2];
            $incid_rea  = empty($line[3]) ? 0  : $line[3];
            $incid_dc   = empty($line[4]) ? 0  : $line[4];
            $incid_rad  = empty($line[5]) ? 0  : $line[5];

            $req .= "('".$dep."', '".$jour."',".$incid_hosp.",".$incid_rea.",".$incid_dc.",".$incid_rad.")," . chr(10);

            $i++;
        }

        try {
            $req = substr($req, 0, -2);
            $sql = $this->dbh->query($req);

            $this->setRegion($tmpTable);

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
          `id`          int         NOT NULL,
          `dep`         varchar(3)  COLLATE utf8mb4_unicode_ci NOT NULL,
          `reg`         varchar(2)  COLLATE utf8mb4_unicode_ci NULL,
          `jour`        date        NOT NULL,
          `incid_hosp`  int         NOT NULL,
          `incid_rea`   int         NOT NULL,
          `incid_dc`    int         NOT NULL,
          `incid_rad`   int         NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD PRIMARY KEY (`id`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` MODIFY `id` int NOT NULL AUTO_INCREMENT";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`dep`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`reg`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`jour`)";
        $this->dbh->query($req);
    }

    /**
     * Ajout des régions
     * @param  string $table    Nom table temporaire
     */
    private function setRegion($table)
    {
        $req = "UPDATE          `$table`     a
                INNER JOIN      geo_depts2018   b
                ON              a.dep = b.dep
                SET             a.reg = b.region";

        $this->dbh->query($req);
    }
}
