<?php

namespace main;

use tools\dbSingleton;
use main\Cache;
use DateTime;
use DateTimeZone;

/**
 * Récupération des Collection de données
 */
class Process
{
    /**
     * Constructeur
     *
     * @param   array   $classList      Liste des jeux de données à récupérer
     */
    public function __construct($classList, $namespace)
    {
        // Delete old caches
        Cache::removeCache();

        $dt = new DateTime('now', new DateTimeZone('UTC'));

        echo 'Start : ' . $dt->format('Y-m-d H:i:s') . chr(10) . chr(10);

        foreach ($classList as $class) {
            $className = '\\' . $namespace . '\\' . $class;
            echo $className . chr(10);

            new $className();
        }

        echo chr(10);
        echo 'End : ' . $dt->format('Y-m-d H:i:s') . chr(10) . chr(10);

        $dbh = dbSingleton::getInstance();

        $req = "INSERT INTO cron (date_crea, namespace) VALUES (NOW(), :namespace)";
        $sql = $dbh->prepare($req);
        $sql->execute([':namespace' => $namespace]);
    }
}
