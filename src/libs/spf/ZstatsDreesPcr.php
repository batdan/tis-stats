<?php

namespace spf;

use tools\dbSingleton;

/**
 * Covid-19 : résultats issus des appariements entre SI-VIC, SI-DEP et VAC-SI
 *
 *      source : https://data.drees.solidarites-sante.gouv.fr/explore/dataset/covid-19-resultats-issus-des-appariements-entre-si-vic-si-dep-et-vac-si/export/?disjunctive.vac_statut
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
class ZstatsDreesPcr
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
        $this->url = 'https://data.drees.solidarites-sante.gouv.fr/explore/dataset/covid-19-resultats-regionaux-issus-des-appariements-entre-si-vic-si-dep-et-vac-s/download/?format=csv&timezone=Europe/Berlin&lang=fr&use_labels_for_header=true&csv_separator=%3B';

        $this->checkStat();
    }

    /**
     * Mise à jour des données
     */
    private function checkStat()
    {
        $file  = file($this->url);
        $table = 'donnees_drees_pcr_covid19';

        $this->dropTable($table);
        $this->createTable($table);

        $req = "INSERT INTO $table (reg, jour, P_f, P_h, P, T_f, T_h, T, cl_age90, pop) VALUES ";

        $i = 0;
        foreach ($file as $line) {
            if ($i == 0) {
                $i++;
                continue;
            }

            echo $line;
            die;

            $line = str_replace(chr(10), '', $line);
            $line = explode(';', $line);

            $req .= "('" . $line[0] . "','" . $line[1] . "',
                    " . $line[2] . "," . $line[3] . "," . $line[4] . ",
                    " . $line[5] . "," . $line[6] . "," . $line[7] . ",
                    " . $line[8] . "," . $line[9] . "),";

            $i++;
        }

        $req = substr($req, 0, -1);
        $sql = $this->dbh->query($req);
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

    /**
     * Création de la table
     * @param  string $table    Nom table
     */
    private function createTable($table)
    {
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
