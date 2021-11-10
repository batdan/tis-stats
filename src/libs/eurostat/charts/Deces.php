<?php

namespace eurostat\charts;

use tools\dbSingleton;
use main\HighChartsCommon;
use main\Cache;
use eurostat\main\Tools;

class Deces
{
    private $cache;
    private $dbh;

    private $chartName;

    private $title;
    private $subTitle;

    private $yAxisLabel;

    private $data;
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

        $this->chartName = 'Deces';

        $this->title    = 'Nb cumulé de décès toutes causes confondues';
        $this->regTitle();

        $maj = Tools::lastMajData();
        $this->subTitle = (!empty($maj)) ? 'Source: Eurostat | ' . $maj : 'Source: Eurostats';

        $this->yAxisLabel = 'Nb cumulé de décès';

        $this->getData();
        $this->highChartsJs();
    }


    /**
     * Récupération des données de la statistique
     * en cache ou en BDD
     */
    public function getData()
    {
        $className = str_replace('\\', '_', get_class($this));
        $fileName  = date('Y-m-d_') . $className;

        $addReq = "";
        $addReqValues = [];
        if (!empty($_SESSION['eurostat_filterCountry'])) {
            $addReq .= " AND geotime = :geotime";
            $addReqValues[':geotime'] = $_SESSION['eurostat_filterCountry'];
            $fileName .= '_country_' . $_SESSION['eurostat_filterCountry'];
        }

        if (!empty($_SESSION['eurostat_filterSex'])) {
            $addReq .= " AND sex = :sex";
            $addReqValues[':sex'] = $_SESSION['eurostat_filterSex'];
            $fileName .= '_sex_' . $_SESSION['eurostat_filterSex'];
        }

        if (!empty($_SESSION['eurostat_filterAge'])) {
            $addReq .= " AND age = :age";
            $addReqValues[':age'] = $_SESSION['eurostat_filterAge'];
            $fileName .= '_age_' . $_SESSION['eurostat_filterAge'];
        }

        if ($this->cache && $this->data = Cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        $req = "SELECT      year, SUM(value) AS sumValue
                FROM        eurostat_demo_magec_opti
                WHERE       value IS NOT NULL
                $addReq
                GROUP BY    year
                ORDER BY    year ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->year] = $res->sumValue;
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
                        fontSize: 14
                    }
                },
                labels: {
                    format: '{value}',
                    style: {
                        color: '#106097',
                        fontSize: 14
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
