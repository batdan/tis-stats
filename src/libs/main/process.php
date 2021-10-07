<?php
namespace main;

/**
 * Récupération des Collection de données
 */
class process
{
    /**
     * Constructeur
     *
     * @param   array   $classList      Liste des jeux de données à récupérer
     */
    public function __construct($classList, $namespace)
    {
        // Delete old caches
        cache::removeCache();

        echo 'Start : ' . date('Y-m-d H:i:s') . chr(10) . chr(10);

        foreach ($classList as $class) {

            $className = '\\' . $namespace . '\\' . $class;
            echo $className . chr(10);

            new $className();
        }

        echo chr(10);
        echo 'End : ' . date('Y-m-d H:i:s') . chr(10) . chr(10);
    }
}
