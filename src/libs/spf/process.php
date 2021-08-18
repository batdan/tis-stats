<?php
namespace spf;

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
    public function __construct($classList)
    {
        // Delete old caches
        cache::removeCache();

        echo date('Y-m-d H:i:s') . chr(10) . chr(10);

        foreach ($classList as $class) {

            $className = '\\spf\\' . $class;
            echo $className . chr(10);

            new $className();
        }

        echo 'End' . chr(10) . chr(10);
    }
}
