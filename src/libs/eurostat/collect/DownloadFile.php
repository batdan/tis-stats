<?php

namespace eurostat\collect;

use DOMDocument;
use DOMXPath;

/**
 * Classe de récupération des jeux de données Eurostats
 */
class DownloadFile
{
    private $url = 'https://ec.europa.eu/eurostat/estat-navtree-portlet-prod/BulkDownloadListing';
    private $uri = [
        'dir'       => 'data',
        'filter'    => 'TSV',
        'sort'      => 1,
        'sort'      => 2,
        'start'     => 'a',
    ];


    /**
     * Récupération d'une collection de données Eurostat
     *
     * @param   string $collectionName        Nom de la collection
     * @return  boolean|array
     *
     */
    public function getDataset($collectionName)
    {
        try {
            $this->uri = array_merge($this->uri, ['start' => substr($collectionName, 0, 1)]);
            $url = $this->url . '?' . http_build_query($this->uri);

            $dom = new DOMDocument('1.0', 'utf-8');
            $dom->loadHTMLFile($url, LIBXML_NOBLANKS);

            $xpath = new DOMXPath($dom);

            // Balise principale master
            $req      = '//a[@href=""]';
            $req      = '//a[contains(@href,"' . $collectionName . '.tsv.gz")]';
            $entries  = $xpath->query($req);

            if ($entries->length) {
                $entry = $entries->item(0);
                $parent = $entry->parentNode->parentNode;

                $name = $this->cleanString($parent->getElementsByTagName('td')->item(0)->nodeValue);
                $size = $this->cleanString($parent->getElementsByTagName('td')->item(1)->nodeValue);
                $type = $this->cleanString($parent->getElementsByTagName('td')->item(2)->nodeValue);
                $date = $this->cleanString($parent->getElementsByTagName('td')->item(3)->nodeValue);
                $date = $this->formatDate($date);

                $linkFile = $entry->getAttribute('href');

                // Récupération du fichier dans un tableau
                $handle = gzopen($linkFile, "r");
                $file = [];
                while (!gzeof($handle)) {
                    $buffer = gzgets($handle, 100000);
                    $file[] = $buffer;
                }
                gzclose($handle);

                return [
                    'name'  => $name,
                    'size'  => $size,
                    'type'  => $type,
                    'date'  => $date,
                    'file'  => $file,
                ];
            }
        } catch (\Exception $e) {
            echo chr(10) . chr(10);
            echo $e->getMessage();
            echo chr(10) . chr(10);
        }

        return false;
    }


    /**
     * Nettoyage des chaînes de caractères
     *
     * @param  string   $str
     * @return string
     */
    private function cleanString($str)
    {
        $str = htmlentities($str);
        $str = str_replace('&nbsp;', '', $str);
        $str = trim($str);

        return $str;
    }


    /**
     * Formatage du datetime
     *
     * @param  string   $dateTime
     * @return string
     */
    private function formatDate($dateTime)
    {
        $dateTime = explode(' ', $dateTime);
        $date = $dateTime[0];
        $time = $dateTime[1];

        $date = explode('/', $date);
        $date = $date[2] . '-' . $date[1] . '-' . $date[0];

        return $date . ' ' . $time;
    }


    /**
     * Conversion pour ramener les code Pays Eurostat à la norme ISO 3166-1 alpha-2
     *
     * @param   string $iso               Code Eurostat
     * @return  string                    Code ISO 3166-1 alpha-2
     */
    public static function convertIsoCodeAlpha2($iso)
    {
        return str_replace(
            ['EL', 'UK'],
            ['GR', 'GB'],
            $iso
        );
    }
}
