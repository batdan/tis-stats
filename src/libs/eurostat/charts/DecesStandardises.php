<?php

namespace eurostat\charts;

use tools\dbSingleton;
use tools\config;
use main\HighChartsCommon;
use main\Cache;
use eurostat\main\Tools;
use Exception;

class DecesStandardises
{
    private $standardYear;      // Année standard
    private $popStandard;       // Population standardisée

    private $cache;
    private $dbh;

    private $chartName;

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

        $this->chartName = 'DecesStandardises';

        $this->title    = 'Décès standardisés toutes causes confondues';
        $this->regTitle();

        $maj = Tools::lastMajData();
        $this->subTitle  = 'Selon la population de la France en 2021 | ';
        $this->subTitle .= (!empty($maj)) ? 'Source: Eurostat | ' . $maj : 'Source: Eurostats';

        $this->yAxisLabel = 'Nb cumulé de décès';

        $this->sandardYear();
        $this->getDataCache();
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

        $this->popStandard = [];

        $keysFilterAge = array_keys(Tools::rangeFilterAge());

        foreach ($keysFilterAge as $key) {
            $req = "SELECT  SUM(value) AS sumValue
                    FROM    eurostat_demo_pjan_opti
                    WHERE   year    = :year
                    AND     geotime = :geotime
                    AND     sex     = :sex
                    AND     age     = :age";

            $sql = $this->dbh->prepare($req);
            $sql->execute([
                ':year'     => $this->standardYear,
                ':sex'      => $_SESSION['eurostat_filterSex'],
                ':geotime'  => $_SESSION['eurostat_filterCountry'],
                ':age'      => $key
            ]);

            if ($sql->rowCount()) {
                $res = $sql->fetch();
                $this->popStandard[$key] = $res->sumValue;
            }
        }

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
                } catch (Exception $e) {
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
        $req = "SELECT      year, SUM(value) AS sumValue
                FROM        eurostat_demo_magec_opti
                WHERE       value IS NOT NULL
                $addReq
                GROUP BY    year
                ORDER BY    year ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $dataDeces[$res->year] = $res->sumValue;
        }

        // Récupération de la population / année
        $years = array_keys($dataDeces);
        $addYears = implode(',', $years);

        $req = "SELECT      year, SUM(value) AS sumValue
                FROM        eurostat_demo_pjan_opti
                WHERE       year IN ($addYears)
                $addReq
                GROUP BY    year
                ORDER BY    year ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $dataPop[$res->year] = $res->sumValue;
        }

        foreach ($dataDeces as $year => $value) {
            if (empty($dataPop[$year])) {
                continue;
            }

            $res = round($value / $dataPop[$year] * $this->popStandard[$rangeAge]);

            if (isset($this->data[$year])) {
                $this->data[$year] += $res;
            } else {
                $this->data[$year] = $res;
            }

            ksort($this->data);
        }

        // createCache
        if ($this->cache) {
            Cache::createCache($fileName, $this->data);
        }
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $years = [];
        $value = [];

        foreach ($this->data as $year => $val) {
            $years[] = "'" . $year . "'";
            $value[] = $val;
        }

        $years = implode(', ', $years);
        $value = implode(', ', $value);

        $credit     = HighChartsCommon::creditLCH();
        $event      = HighChartsCommon::exportImgLogo();
        $xAxis      = HighChartsCommon::xAxisYears($years);
        $legend     = HighChartsCommon::legend();
        $responsive = HighChartsCommon::responsive();

        $barsColor  = Tools::getSexColor()[$_SESSION['eurostat_filterSex']];

        $this->highChartsJs = <<<eof
        Highcharts.chart('{$this->chartName}', {

            $credit

            $event

            chart: {
                type: 'column',
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
                opposite: true
            }],

            $xAxis

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
