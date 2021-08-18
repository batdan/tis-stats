<?php
namespace spf;

class cache
{
    private static $pathCache = __DIR__ . '/../../cache/';


    /**
     * Vérification de l'existence d'un cache
     * et retour des données s'il existe
     *
     * @param  string   $fileName   Nom du fichier
     * @return boolean|object
     */
    public static function getCache($fileName)
    {
        $pathFile = static::$pathCache . $fileName;

        if (file_exists($pathFile)) {
            $file = file($pathFile);
            return json_decode($file[0], true);
        } else {
            return false;
        }
    }


    /**
     * Suppression des caches anciens
     */
    public static function removeCache()
    {
        $cdir = scandir(static::$pathCache);

        foreach($cdir as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            // if (substr($file, 0, 10) != date('Y-m-d')) {
                unlink(static::$pathCache . $file);
            // }
        }
    }


    /**
     * Création d'un fichier pour stoquer un cache
     *
     * @param  string   $fileName   Nom du fichier
     * @param  object   $data       Données à mettre en cache
     */
    public static function createCache($fileName, $data)
    {
        $file = fopen(static::$pathCache . $fileName, 'w');
        fwrite($file, json_encode($data));
        fclose($file);
    }
}
