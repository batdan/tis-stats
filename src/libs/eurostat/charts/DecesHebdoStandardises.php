<?php

namespace eurostat\charts;

use tools\dbSingleton;
use tools\config;
use main\HighChartsCommon;
use main\Cache;
use eurostat\main\Tools;
use DateTime;

class DecesHebdoStandardises
{
    private $standardYear;      // Année standard
    private $popStandard;       // Population standardisée

    private $cache;
    private $dbh;

    private $chartName;
    private $measures;

    private $title;
    private $subTitle;

    private $yAxisLabel;

    private $data = [];
    private $highChartsJs;


    /**
     * @param boolean $cache    Activation ou non du cache des résultats de requêtes
     */
    public function __construct(bool $cache = true)
    {
        $this->cache = $cache;

        if (!$cache) {
            echo '<pre>Attention, les caches ne sont pas activés !</pre>';
        }

        $this->dbh = dbSingleton::getInstance();

        $this->chartName = 'DecesHebdoStandardises';

        $this->title    = 'Décès hebdomadaires standardisés toutes causes confondues';
        $this->regTitle();

        $maj = Tools::lastMajData();
        $this->subTitle  = "Selon l&#039année 2021 | ";
        $this->subTitle .= (!empty($maj)) ? 'Source: Eurostat | ' . $maj : 'Source: Eurostats';

        $this->yAxisLabel = 'Nb cumulé de décès par semaine';

        $this->sandardYear();
        $this->getDataCache();
        $this->getMeasures();
        $this->highChartsJs();
    }


    /**
     * Calcul et mise en cache de la population standardisée
     * Choix d'une année en France
     */
    public function sandardYear()
    {
        // Année standardisé
        $eurostat = config::getConfig('eurostat');
        $this->standardYear = $eurostat['standardYear'];

        // Gestion des caches
        $className = str_replace('\\', '_', get_class($this));
        $fileName  = date('Y-m-d_') . $className;
        $fileName .= '_country_' . $_SESSION['eurostat_filterCountry'];
        $fileName .= '_standardYear_' . $this->standardYear;
        $fileName .= '_sex_' . $_SESSION['eurostat_filterSex'];

        if ($this->cache && $this->popStandard = Cache::getCache($fileName)) {
            return;
        }

        $pop = [];

        $keysFilterAge = array_keys(Tools::rangeFilterAge());

        foreach ($keysFilterAge as $key) {
            $req = "SELECT      year, SUM(value) AS sumValue
                    FROM        eurostat_demo_pjan_opti
                    WHERE       year    >= :year
                    AND         geotime = :geotime
                    AND         sex     = :sex
                    AND         age     = :age
                    GROUP BY    year
                    ORDER BY    YEAR ASC";

            $sql = $this->dbh->prepare($req);

            $sql->execute([
                ':year'     => $this->standardYear - 11,
                ':sex'      => $_SESSION['eurostat_filterSex'],
                ':geotime'  => $_SESSION['eurostat_filterCountry'],
                ':age'      => $key
            ]);

            while ($res = $sql->fetch()) {
                $pop[$key][$res->year . 'W53'] = $res->sumValue;
            }
        }

        foreach ($pop as $age => $yearWeeks) {
            foreach ($yearWeeks as $yearWeek => $val) {
                $year = substr($yearWeek, 0, 4);
                $week = substr($yearWeek, 4, 3);

                if (isset($pop[$age][($year - 1) . 'W53']) && $week == 'W53') {
                    $popYearPrec = $pop[$age][($year - 1) . 'W53'];
                    $popYear = $val;

                    // Calcul du différentiel entre 2 semaines (évolution linéaire)
                    $nbWeeks = ($year == date('Y')) ? intval(date('W')) : 53;
                    $diff = round(($popYear - $popYearPrec) / $nbWeeks, 2);

                    // Ajout des semaines intermédiaires
                    for ($i = 1; $i <= $nbWeeks; $i++) {
                        $keyW = $year . 'W' . str_pad($i, 2, '0', STR_PAD_LEFT);
                        $pop[$age][$keyW] = round($popYearPrec + ($i * $diff));
                    }
                }

                ksort($pop[$age]);
            }
        }

        $this->popStandard = $pop;

        // createCache
        if ($this->cache) {
            Cache::createCache($fileName, $this->popStandard);
        }
    }


    public function getDataCache()
    {
        $className = str_replace('\\', '_', get_class($this));
        $fileName  = date('Y-m-d_') . $className;

        $addReq = [];
        $addReqValues = [];
        if (!empty($_SESSION['eurostat_filterCountry'])) {
            $addReq['geotime'] = " AND geotime = :geotime";
            $addReqValues[':geotime'] = $_SESSION['eurostat_filterCountry'];
            $fileName .= '_country_' . $_SESSION['eurostat_filterCountry'];
        }

        if (!empty($_SESSION['eurostat_filterSex'])) {
            $addReq['sex'] = " AND sex = :sex";
            $addReqValues[':sex'] = $_SESSION['eurostat_filterSex'];
            $fileName .= '_sex_' . $_SESSION['eurostat_filterSex'];
        }

        if (!empty($_SESSION['eurostat_filterAge'])) {
            $fileName .= '_age_' . $_SESSION['eurostat_filterAge'];
        }

        if ($this->cache && $this->data = Cache::getCache($fileName)) {
            return;
        } else {
            // Pour toutes les tranches d'âge hors 'TOTAL'
            if ($_SESSION['eurostat_filterAge'] != 'TOTAL') {
                if (!empty($_SESSION['eurostat_filterAge'])) {
                    $addReq['age'] = " AND age = :age";
                    $addReqValues[':age'] = $_SESSION['eurostat_filterAge'];
                }

                $addReqStr = $addReq['geotime'] . ' ' . $addReq['sex'] . ' ' . $addReq['age'];
                $this->getData($addReqStr, $addReqValues, $fileName, $_SESSION['eurostat_filterAge']);

            // Pour la tranches d'âge 'TOTAL'
            } else {
                try {
                    $keysFilterAge = array_keys(Tools::rangeFilterAge());
                    unset($keysFilterAge[0]);

                    foreach ($keysFilterAge as $key) {
                        $addReq['age'] = " AND age = :age";
                        $addReqValues[':age'] = $key;
                        $addReqStr = $addReq['geotime'] . ' ' . $addReq['sex'] . ' ' . $addReq['age'];

                        $this->getData($addReqStr, $addReqValues, $fileName, $key);
                    }
                } catch (\Exception $e) {
                    error_log($e->getMessage());
                }
            }
        }
    }


    /**
     * Récupération des données de la statistique
     * en cache ou en BDD
     */
    public function getData($addReq, $addReqValues, $fileName, $rangeAge)
    {
        $dataDeces  = [];
        $dataPop    = [];

        // Récupération des décès
        $req = "SELECT      year_week, value
                FROM        eurostat_demo_r_mwk_05
                WHERE       value IS NOT NULL
                $addReq
                AND         year_week >= :year_week
                ORDER BY    year_week ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute(array_merge($addReqValues, [':year_week' => $this->standardYear - 10]));

        while ($res = $sql->fetch()) {
            $dataDeces[$res->year_week] = $res->value;
        }

        // Récupération de la population / année
        $yearWeek = array_keys($dataDeces);
        $addYearWeek = implode(',', $yearWeek);

        foreach ($dataDeces as $yearWeek => $value) {
            $checkPop = true;
            if (empty($this->popStandard[$addReqValues[':age']][$yearWeek])) {
                $checkPop = false;
            }

            // Population année / semaine
            if ($checkPop) {
                $popYearWeek = $this->popStandard[$addReqValues[':age']][$yearWeek];
            } else {
                $lastYearEnd = (intval(substr($yearWeek, 0, 4)) - 1) . 'W53';

                if (empty($this->popStandard[$addReqValues[':age']][$lastYearEnd])) {
                    continue;
                }

                $popYearWeek = $this->popStandard[$addReqValues[':age']][$lastYearEnd];
            }

            // Population année / semaine standardisé
            $yearWeekStd = $this->standardYear - 1 . substr($yearWeek, 4, 3);
            $popYearWeekStd = $this->popStandard[$addReqValues[':age']][$yearWeekStd];

            // Calcul
            $res = round($value / $popYearWeek * $popYearWeekStd);

            if (isset($this->data[$yearWeek])) {
                $this->data[$yearWeek] += $res;
            } else {
                $this->data[$yearWeek] = $res;
            }

            ksort($this->data);
        }

        // createCache
        if ($this->cache) {
            Cache::createCache($fileName, $this->data);
        }
    }


    /**
     * Récupération des confinements
     */
    private function getMeasures()
    {
        $this->measures = [];
        
        $req = "SELECT  date_start, date_end 
                FROM    ecdc_response_measure 
                WHERE   iso_3166_1_alpha_2  = :iso_3166_1_alpha_2 
                AND     response_measure    = :response_measure";

        $sql = $this->dbh->prepare($req);
        $sql->execute([
            ':iso_3166_1_alpha_2'   => $_SESSION['eurostat_filterCountry'],
            ':response_measure'     => 'StayHomeOrder',
        ]);

        while ($res = $sql->fetch()) {
            $this->measures[] = [
                'date_start'    => $res->date_start,
                'date_end'      => $res->date_end,
            ];
        }
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $yearWeeks = [];
        $value = [];

        foreach ($this->data as $yearWeek => $val) {
            $yearWeeks[] = "'" . $yearWeek . "'";
            $value[] = $val;
        }

        $moyenne = Tools::moyenneTunnel($value, 0, 66);

        if (count($yearWeeks) == 0) {
            $yearStart = 2021;
        } else {
            $yearStart = substr($yearWeeks[0], 1, 4);
        }

        $yearWeeks  = implode(', ', $yearWeeks);
        $value      = implode(', ', $value);

        $credit     = HighChartsCommon::creditLCH();
        $event      = HighChartsCommon::exportImgLogo();
        $legend     = HighChartsCommon::legend();
        $responsive = HighChartsCommon::responsive();

        $barsColor  = Tools::getSexColor()[$_SESSION['eurostat_filterSex']];

        $plotDand = [];
        foreach ($this->measures as $measure) {
            $d = new DateTime($measure['date_start']);
            $date_start_Y = $d->format('Y');
            $date_start_m = intval($d->format('m')) - 1;
            $date_start_d = intval($d->format('d'));

            $d = new DateTime($measure['date_end']);
            $date_end_Y = $d->format('Y');
            $date_end_m = intval($d->format('m')) - 1;
            $date_end_d = intval($d->format('d'));

            $plotDand[] = <<<eof
{
            color: '#fbe4c2',
            from: Date.UTC($date_start_Y, $date_start_m, $date_start_d),
            to: Date.UTC($date_end_Y, $date_end_m, $date_end_d),
            label: { 
                text: 'Confinement', 
                rotation: -90,
                align: 'left', 
                x: -5,
                y: 80
            }
        }
eof;
        }

        $plotDand  = implode(',', $plotDand);
        $plotDands = "plotBands: [$plotDand]";

        $this->highChartsJs = <<<eof
        Highcharts.chart('{$this->chartName}', {

            $credit

            $event

            chart: {
                type: 'line',
                height: 580,
                events: {
                    load: function () {
                        $('#ajaxLoader').css('display', 'none');
                    }
                }
            },

            title: {
                text: '{$this->title}'
            },

            subtitle: {
                text: '{$this->subTitle}'
            },

            plotOptions: {
                series: {
                    groupPadding: 0,
                    maxPointWidth: 55
                }
            },

            yAxis: [{
                title: {
                    text: '{$this->yAxisLabel}',
                    style: {
                        color: '#106097',
                        fontSize: 16
                    }
                },
                labels: {
                    format: '{value}',
                    style: {
                        color: '#106097',
                        fontSize: 16
                    },
                    formatter: function() {
                        return Highcharts.numberFormat(this.value, 0, '.', ' ');
                    }
                },
                plotLines: [{
                    color: '#FF964F',
                    width: 1.5,
                    dashStyle: 'DashDot',
                    value: {$moyenne['max']}
                },{
                    color: '#FF964F',
                    width: 1,
                    value: {$moyenne['moy']}
                },{
                    color: '#FF964F',
                    width: 1.5,
                    dashStyle: 'DashDot',
                    value: {$moyenne['min']}
                }],
                opposite: true
            }],

            xAxis: {
                type: 'datetime',
                tickInterval: 24 * 3600 * 1000 * 365,
                dateTimeLabelFormats: {
                    day: "%e. %b",
                    month: "%b '%y",
                    year: "%Y"
                },
                formatter: function() {
                    var x = new Date(this.value);
                    return x.getFullYear() + '<br /> Month: ' + x.getMonth();
                },

                // categories: [$yearWeeks],
                gridLineWidth: 1,
                labels: {
                    rotation: 0,
                    style: {
                        fontSize: 16
                    }
                },
                tickWidth: 1,
                tickLength: 7,
                $plotDands
            },

            $legend

            series: [{
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxisLabel}',
                color: '$barsColor',
                yAxis: 0,
                data: [$value]
            }],

            plotOptions: {
                series: {
                    pointStart: Date.UTC($yearStart, 0, 1),
                    pointInterval: 7 * 24 * 3600 * 1000     // One week
                }
            },

            $responsive
        });
        eof;
    }


    private function regTitle()
    {
        // Pays
        $this->title .= ' | ' . Tools::getCountries()[$_SESSION['eurostat_filterCountry']];

        // Sexe
        if ($_SESSION['eurostat_filterSex'] != 'T') {
            $this->title .= ' | ' . Tools::getSex()[$_SESSION['eurostat_filterSex']];
        }

        // Ages
        if ($_SESSION['eurostat_filterAge'] != 'TOTAL') {
            $this->title .= ' | ' . Tools::rangeFilterAge()[$_SESSION['eurostat_filterAge']];
        }
    }


    /**
     * Redu HTML
     */
    public function render()
    {
        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [
            'charts'    => [true,  'col-lg-5'],
            'countries' => [true,  'col-lg-3'],
            'sex'       => [true,  'col-lg-2'],
            'age'       => [true,  'col-lg-2'],
        ];

        echo Render::html(
            $this->chartName,
            $this->title,
            $this->highChartsJs,
            $backLink,
            $filterActiv
        );
    }
}
