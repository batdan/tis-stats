<?php
namespace spf;

use tools\dbSingleton;

/**
 * Données relatives aux résultats des tests virologiques COVID-19 -> quotidien
 *
 *      source : https://www.data.gouv.fr/fr/datasets/donnees-relatives-aux-resultats-des-tests-virologiques-covid-19/
 *      compilation : sp-pos-quot-reg-2021-08-04-19h09.csv
 *
 *      Jeux de données : https://www.data.gouv.fr/fr/organizations/sante-publique-france/#organization-datasets
 *
 *
 * Documentation :
 *
 * Colonne  Type            Description_FR                                      Exemple
 *
 * reg      String          Region                                              2.0
 * jour     Date            Jour                                                2020-05-13
 * p_f      integer         Nombre de test positif chez les femmes              1688.0
 * p_h      integer         Nombre de test positif chez les hommes              1688.0
 * p        integer         Nombre de test positifs                             34.0
 * t_f      integer         Nombre de test effectués chez les femmes            93639.0
 * t_h      integer         Nombre de test effectués chez les hommes            93639.0
 * t        integer         Nombre de test réalisés                             2141.0
 * cl_age90 integer         Classe d'age                                        09
 * pop      integer         Population de reference
 *                          (du departement, de la région, nationale)           656955.0
 */
class statsLaboPcr
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
        $this->url = 'https://www.data.gouv.fr/fr/datasets/r/001aca18-df6a-45c8-89e6-f82d689e6c01';

        $this->checkStat();
    }

    /**
     * Mise à jour des données
     */
    private function checkStat()
    {
        $file  = file($this->url);

        $table = 'donnees_labo_pcr_covid19';
        $tmpTable = $table . '_tmp';

        $this->createTable($tmpTable);

        $req = "INSERT INTO $tmpTable (reg, jour, P_f, P_h, P, T_f, T_h, T, cl_age90, pop) VALUES ";

        $i=0;
        foreach ($file as $line) {
            if ($i==0) {
                $i++;
                continue;
            }

            $line = str_replace(chr(10), '', $line);
            $line = explode(';', $line);

            $req .= "('".$line[0]."','".$line[1]."',
                    ".$line[2].",".$line[3].",".$line[4].",
                    ".$line[5].",".$line[6].",".$line[7].",
                    ".$line[8].",".$line[9]."),";

            $i++;
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
     * Création de la table
     * @param  string   $table   Nom table temporaire
     */
    private function createTable($table)
    {
        $this->dropTable($table);

        $req = "CREATE TABLE `$table` (
          `id`          int         NOT NULL,
          `reg`         varchar(2)  COLLATE utf8mb4_unicode_ci NULL,
          `jour`        date        NOT NULL,
          `P_f`         int         NOT NULL,
          `P_h`         int         NOT NULL,
          `P`           int         NOT NULL,
          `T_f`         int         NOT NULL,
          `T_h`         int         NOT NULL,
          `T`           int         NOT NULL,
          `cl_age90`    int         NOT NULL,
          `pop`         int         NOT NULL
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
