<?php
namespace eurostat\main;

use tools\dbSingleton;

class tools
{
    /**
     * rangeFilterAge descriptions
     *
     * Y_LT5 : Less than 5 years | http://dd.eionet.europa.eu/vocabularyconcept/eurostat/agechild/Y_LT5/view
     * Y_GE90 : 90 years or over | http://dd.eionet.europa.eu/vocabularyconcept/eurostat/agechild/Y_GE90/view
     *
     * @return array
     */
    public static function rangeFilterAge()
    {
        return [
            'TOTAL'  => 'Tous les âges',
            'Y_LT5'  => '0 à 4 ans',
            'Y5-9'   => '5 à 9 ans',
            'Y10-14' => '10 à 14 ans',
            'Y15-19' => '15 à 19 ans',
            'Y20-24' => '20 à 24 ans',
            'Y25-29' => '25 à 29 ans',
            'Y30-34' => '30 à 34 ans',
            'Y35-39' => '35 à 39 ans',
            'Y40-44' => '40 à 44 ans',
            'Y45-49' => '45 à 49 ans',
            'Y50-54' => '50 à 54 ans',
            'Y55-59' => '55 à 59 ans',
            'Y60-64' => '60 à 64 ans',
            'Y65-69' => '65 à 69 ans',
            'Y70-74' => '70 à 74 ans',
            'Y75-79' => '75 à 79 ans',
            'Y80-84' => '80 à 84 ans',
            'Y85-89' => '85 à 89 ans',
            'Y_GE90' => '90 ans et plus',
        ];
    }


    /**
     * rangeFilterAge descriptions
     *
     * @return array
     */
    public static function rangeFilterAge2($action = '')
    {
        $ages = [
            'Y_LT5'  => '0-4',
            'Y5-9'   => '5-9',
            'Y10-14' => '10-14',
            'Y15-19' => '15-19',
            'Y20-24' => '20-24',
            'Y25-29' => '25-29',
            'Y30-34' => '30-34',
            'Y35-39' => '35-39',
            'Y40-44' => '40-44',
            'Y45-49' => '45-49',
            'Y50-54' => '50-54',
            'Y55-59' => '55-59',
            'Y60-64' => '60-64',
            'Y65-69' => '65-69',
            'Y70-74' => '70-74',
            'Y75-79' => '75-79',
            'Y80-84' => '80-84',
            'Y85-89' => '85-89',
            'Y_GE90' => '90 +',
        ];

        if ($action == 'format') {
            $yRange = [];
            foreach ($ages as $age) {
                $yRange[] = "'" . $age . "'";
            }
            return implode(',', $yRange);
        }

        return $ages;
    }


    /**
     * Magec ajout des filtres d'age dans les requêtes
     *
     * @param  array    $range      Ajout filtre requête
     * @return string
     */
    public static function magecFilterAge($range)
    {
        switch ($range)
        {
            case 'TOTAL'  : return " AND age = 'TOTAL'";
            case 'Y_LT5'  : return " AND (age='Y_LT1' OR age='Y1'  OR age='Y2'  OR age='Y3'  OR age='Y4'  OR age='Y_LT5')";
            case 'Y5-9'   : return " AND (age='Y5'    OR age='Y6'  OR age='Y7'  OR age='Y8'  OR age='Y9'  OR age='Y5-9')";
            case 'Y10-14' : return " AND (age='Y10'   OR age='Y11' OR age='Y12' OR age='Y13' OR age='Y14' OR age='Y10-14')";
            case 'Y15-19' : return " AND (age='Y15'   OR age='Y16' OR age='Y17' OR age='Y18' OR age='Y19' OR age='Y15-19')";
            case 'Y20-24' : return " AND (age='Y20'   OR age='Y21' OR age='Y22' OR age='Y23' OR age='Y24' OR age='Y20-24')";
            case 'Y25-29' : return " AND (age='Y25'   OR age='Y26' OR age='Y27' OR age='Y28' OR age='Y29' OR age='Y25-29')";
            case 'Y30-34' : return " AND (age='Y30'   OR age='Y31' OR age='Y32' OR age='Y33' OR age='Y34' OR age='Y30-34')";
            case 'Y35-39' : return " AND (age='Y35'   OR age='Y36' OR age='Y37' OR age='Y38' OR age='Y39' OR age='Y35-39')";
            case 'Y40-44' : return " AND (age='Y40'   OR age='Y41' OR age='Y42' OR age='Y43' OR age='Y44' OR age='Y40-44')";
            case 'Y45-49' : return " AND (age='Y45'   OR age='Y46' OR age='Y47' OR age='Y48' OR age='Y49' OR age='Y45-49')";
            case 'Y50-54' : return " AND (age='Y50'   OR age='Y51' OR age='Y52' OR age='Y53' OR age='Y54' OR age='Y50-54')";
            case 'Y55-59' : return " AND (age='Y55'   OR age='Y56' OR age='Y57' OR age='Y58' OR age='Y59' OR age='Y55-59')";
            case 'Y60-64' : return " AND (age='Y60'   OR age='Y61' OR age='Y62' OR age='Y63' OR age='Y64' OR age='Y60-64')";
            case 'Y65-69' : return " AND (age='Y65'   OR age='Y66' OR age='Y67' OR age='Y68' OR age='Y69' OR age='Y65-69')";
            case 'Y70-74' : return " AND (age='Y70'   OR age='Y71' OR age='Y72' OR age='Y73' OR age='Y74' OR age='Y70-74')";
            case 'Y75-79' : return " AND (age='Y75'   OR age='Y76' OR age='Y77' OR age='Y78' OR age='Y79' OR age='Y75-79')";
            case 'Y80-84' : return " AND (age='Y80'   OR age='Y81' OR age='Y82' OR age='Y83' OR age='Y84' OR age='Y80-84')";
            case 'Y85-89' : return " AND (age='Y85'   OR age='Y86' OR age='Y87' OR age='Y88' OR age='Y89' OR age='Y85-89')";
            case 'Y_GE90' : return " AND (age='Y90'   OR age='Y91' OR age='Y92' OR age='Y93' OR age='Y94'
                                     OR   age='Y95'   OR age='Y96' OR age='Y97' OR age='Y98' OR age='Y99'
                                     OR   age='Y_OPEN' OR age='Y_GE90')";

        }
    }


    /**
     * Magec ajout des filtres d'age dans les requêtes
     *
     * @param  array    $range      Ajout filtre requête
     * @return string
     */
    public static function magecFilterAge2($range)
    {
        switch ($range)
        {
            case 'TOTAL'  : return " AND age = 'TOTAL'";
            case 'Y_LT5'  : return " AND (age='Y_LT1' OR age='Y1'  OR age='Y2'  OR age='Y3'  OR age='Y4')";
            case 'Y5-9'   : return " AND (age='Y5'    OR age='Y6'  OR age='Y7'  OR age='Y8'  OR age='Y9')";
            case 'Y10-14' : return " AND (age='Y10'   OR age='Y11' OR age='Y12' OR age='Y13' OR age='Y14')";
            case 'Y15-19' : return " AND (age='Y15'   OR age='Y16' OR age='Y17' OR age='Y18' OR age='Y19')";
            case 'Y20-24' : return " AND (age='Y20'   OR age='Y21' OR age='Y22' OR age='Y23' OR age='Y24')";
            case 'Y25-29' : return " AND (age='Y25'   OR age='Y26' OR age='Y27' OR age='Y28' OR age='Y29')";
            case 'Y30-34' : return " AND (age='Y30'   OR age='Y31' OR age='Y32' OR age='Y33' OR age='Y34')";
            case 'Y35-39' : return " AND (age='Y35'   OR age='Y36' OR age='Y37' OR age='Y38' OR age='Y39')";
            case 'Y40-44' : return " AND (age='Y40'   OR age='Y41' OR age='Y42' OR age='Y43' OR age='Y44')";
            case 'Y45-49' : return " AND (age='Y45'   OR age='Y46' OR age='Y47' OR age='Y48' OR age='Y49')";
            case 'Y50-54' : return " AND (age='Y50'   OR age='Y51' OR age='Y52' OR age='Y53' OR age='Y54')";
            case 'Y55-59' : return " AND (age='Y55'   OR age='Y56' OR age='Y57' OR age='Y58' OR age='Y59')";
            case 'Y60-64' : return " AND (age='Y60'   OR age='Y61' OR age='Y62' OR age='Y63' OR age='Y64')";
            case 'Y65-69' : return " AND (age='Y65'   OR age='Y66' OR age='Y67' OR age='Y68' OR age='Y69')";
            case 'Y70-74' : return " AND (age='Y70'   OR age='Y71' OR age='Y72' OR age='Y73' OR age='Y74')";
            case 'Y75-79' : return " AND (age='Y75'   OR age='Y76' OR age='Y77' OR age='Y78' OR age='Y79')";
            case 'Y80-84' : return " AND (age='Y80'   OR age='Y81' OR age='Y82' OR age='Y83' OR age='Y84')";
            case 'Y85-89' : return " AND (age='Y85'   OR age='Y86' OR age='Y87' OR age='Y88' OR age='Y89')";
            case 'Y_GE90' : return " AND (age='Y90'   OR age='Y91' OR age='Y92' OR age='Y93' OR age='Y94'
                                     OR   age='Y95'   OR age='Y96' OR age='Y97' OR age='Y98' OR age='Y99'
                                     OR   age='Y_OPEN' OR age='Y_GE90')";

        }
    }


    public static function getCountries()
    {
        $dbh = dbSingleton::getInstance();

        $req = "SELECT      pays, iso_3166_1_alpha_2
                FROM        base_countries
                WHERE       eurostat_activ = 1
                ORDER BY    pays";
        $sql = $dbh->query($req);

        $countries = [];
        while ($res = $sql->fetch()) {
            $countries[$res->iso_3166_1_alpha_2] = $res->pays;
        }

        return $countries;
    }


    public static function getSex()
    {
        return [
            'T' => 'Tous',
            'F' => 'Femmes',
            'M' => 'Hommes',
        ];
    }


    public static function getSexColor()
    {
        return [
            'T' => '#236b53',
            'F' => '#bb45a0',
            'M' => '#1485b2',
        ];
    }


    public static function lastMajData()
    {
        $dbh = dbSingleton::getInstance();
        $date = '';

        $req = "SELECT DATE_FORMAT(date_crea, '%Y-%m-%d %H:%i') AS myDate FROM cron WHERE namespace = :namespace ORDER BY id DESC LIMIT 1";
        $sql = $dbh->prepare($req);
        $sql->execute([':namespace' => 'collect\eurostat']);

        if ($sql->rowCount()) {
            $res  = $sql->fetch();
            $date = $res->myDate;
        }

        return $date;
    }


    /**
     * Calcul de la médiane
     * @param   array       $numbers
     * @return  integer
     */
    public static function median(array $numbers=[])
    {
        rsort($numbers);
        $mid = (int)(count($numbers) / 2);
        return ($mid % 2 != 0) ? $numbers[$mid] : (($numbers[$mid-1]) + $numbers[$mid]) / 2;
    }


    /**
     * Calcul de la moyenne
     * @param   array       $numbers
     * @param   integer     $precision
     * @return  integer
     */
    public static function moyenne(array $numbers=[], $precision=0)
    {
        if (empty($numbers)) {
            return 0;
        }

        return round(array_sum($numbers) / count($numbers), 0);
    }


    /**
     * Calcul de la moyenne tunnel
     * @param   array       $numbers
     * @param   integer     $percent
     * @return  integer
     */
    public static function moyenneTunnel(array $numbers=[], $precision=0, $percent=70)
    {
        $moyenne = self::moyenne($numbers, $precision);
        $amplitude = intval(max($numbers)) - intval(min($numbers));

        $diff = ($percent/ 100) * ($amplitude / 2);

        $moyenneHaute = $moyenne + round($diff, $precision);
        $moyenneBasse = $moyenne - round($diff, $precision);

        return [
            "max" => $moyenneHaute,
            "moy" => $moyenne,
            "min" => $moyenneBasse
        ];
    }
}
