<?php
namespace main;

class cache
{
    private static $pathCache   = __DIR__ . '/../../cache/';
    private static $md5Cache    = false;


    /**
     * Vérification de l'existence d'un cache
     * et retour des données s'il existe
     *
     * @param  string   $fileName   Nom du fichier
     * @return boolean|object
     */
    public static function getCache($fileName)
    {
        $pathFile = self::pathFile($fileName);

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
        $cdir = scandir(self::$pathCache);

        foreach ($cdir as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            unlink(self::$pathCache . $file);
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
        $file = fopen(self::pathFile($fileName), 'w');
        fwrite($file, json_encode($data));
        fclose($file);
    }


    /**
     * Convertion des noms de fichier de cache en MD5
     *
     * @param  string   $fileName   Nom du fichier
     * @return string
     */
    private static function pathFile($fileName)
    {
        if (self::$md5Cache) {
            $fileName = md5($fileName);
        }

        return self::$pathCache . $fileName;
    }
}
