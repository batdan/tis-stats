<?php

namespace spf\charts;

use tools\dbSingleton;
use main\HighChartsCommon;
use main\Cache;

class NbCumuleDeces
{
    private $cache;
    private $dbh;

    private $chartName;

    private $title;
    private $subTitle;

    private $yAxis1Label;
    private $yAxis2Label;

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

        $this->chartName = 'NbCumuleDeces';

        $this->title    = 'Nb cumulé de décès covid-19 | Taux de positivité covid-19';
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours) | Données hospitalières';

        $this->yAxis1Label = 'Nb cumulé de décès covid-19';

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
        if (!empty($_SESSION['spf_filterRegionId']) && is_numeric($_SESSION['spf_filterRegionId'])) {
            $addReq .= " AND reg = :reg";
            $addReqValues[':reg'] = $_SESSION['spf_filterRegionId'];
            $fileName .= '_reg_' . $_SESSION['spf_filterRegionId'];
        }

        if (!empty($_SESSION['spf_filterInterval']) && $_SESSION['spf_filterInterval'] != 'all') {
            $addReq .= " AND jour >= :jour";
            $addReqValues[':jour'] = $_SESSION['spf_filterInterval'];
            $fileName .= '_interval_' . $_SESSION['spf_filterInterval'];
        }

        $addReq .= " AND cl_age90 = :cl_age90";
        if (!empty($_SESSION['spf_filterAge']) && $_SESSION['spf_filterAge'] != '0') {
            $addReqValues[':cl_age90'] = $_SESSION['spf_filterAge'];
            $fileName .= '_age_' . $_SESSION['spf_filterAge'];
        } else {
            $addReqValues[':cl_age90'] = 0;
        }

        if ($this->cache && $this->data = Cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        $req = "SELECT      jour,
                            SUM(dc) AS sum_dc

                FROM        donnees_hp_cumule_age_covid19_reg_calc_lisse7j

                WHERE       1 $addReq

                GROUP BY    jour
                ORDER BY    jour ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->jour]['sum_dc'] = (!isset($res->sum_dc)) ? null : $res->sum_dc;
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
        $jours  = [];
        $dc     = [];

        foreach ($this->data as $jour => $res) {
            $jours[] = "'" . $jour . "'";
            $dc[]    = round($res['sum_dc'], 2);
        }

        $jours  = implode(', ', $jours);
        $dc     = implode(', ', $dc);

        $credit     = HighChartsCommon::creditLCH();
        $event      = HighChartsCommon::exportImgLogo();
        $xAxis      = HighChartsCommon::xAxis($jours);
        $legend     = HighChartsCommon::legend();
        $responsive = HighChartsCommon::responsive();

        $this->highChartsJs = <<<eof
        Highcharts.chart('{$this->chartName}', {

            $credit

            $event

            chart: {
                type: 'spline',
                height: 580
            },

            title: {
                text: '{$this->title}'
            },

            subtitle: {
                text: '{$this->subTitle}'
            },

            yAxis: [{
                title: {
                    text: '{$this->yAxis1Label}',
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
                name: '{$this->yAxis1Label}',
                color: '#106097',
                yAxis: 0,
                data: [$dc]
            }],

            $responsive
        });
        eof;
    }


    private function regTitle()
    {
        $this->title .= ($_SESSION['spf_filterRegionId'] == 0)
                            ? ' | ' . $_SESSION['spf_filterRegionName']
                            : ' | Région : ' . HighChartsCommon::chartText($_SESSION['spf_filterRegionName']);
    }


    /**
     * Redu HTML
     */
    public function render()
    {
        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [
            'charts'    => [true, 'col-lg-4'],
            'region'    => [true, 'col-lg-4'],
            'interval'  => [true, 'col-lg-2'],
            'age'       => [true, 'col-lg-2'],
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
