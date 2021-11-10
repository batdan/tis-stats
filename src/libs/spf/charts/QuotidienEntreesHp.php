<?php

namespace spf\charts;

use tools\dbSingleton;
use main\HighChartsCommon;
use main\Cache;

class QuotidienEntreesHp
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

        $this->chartName = 'QuotidienEntreesHp';

        $this->title    = "Nombre quotidien d'hospitalisations covid-19 | Taux de positivité covid-19";
        $this->title    = HighChartsCommon::chartText($this->title);
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours) | Données hospitalières';

        $this->yAxis1Label = '% de positifs sur la population testée';
        $this->yAxis2Label = "Nb quotidien d'hospitalisations";
        $this->yAxis2Label = HighChartsCommon::chartText($this->yAxis2Label);

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

        if ($this->cache && $this->data = Cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        $req = "SELECT      jour,
                            SUM(T) AS sum_T,
                            SUM(P) AS sum_P

                FROM        donnees_labo_pcr_covid19_calc_lisse7j

                WHERE       1 $addReq

                GROUP BY    jour
                ORDER BY    jour ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->jour] = [
                'sum_T' => $res->sum_T,
                'sum_P' => $res->sum_P,
            ];
        }

        $req = "SELECT      jour,
                            SUM(hosp) AS sum_hosp

                FROM        donnees_hp_quotidien_covid19_reg_calc_lisse7j

                WHERE       1 $addReq

                GROUP BY    jour
                ORDER BY    jour ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->jour]['sum_hosp'] = $res->sum_hosp;
        }

        ksort($this->data);

        foreach ($this->data as $k => $v) {
            $this->data[$k]['sum_T']    = (!isset($v['sum_T']))     ? null : $v['sum_T'];
            $this->data[$k]['sum_P']    = (!isset($v['sum_P']))     ? null : $v['sum_P'];
            $this->data[$k]['sum_hosp'] = (!isset($v['sum_hosp']))  ? null : $v['sum_hosp'];
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
        $jours      = [];
        $hosp       = [];
        $positivite = [];

        foreach ($this->data as $jour => $res) {
            $jours[] = "'" . $jour . "'";
            $hosp[]  = round($res['sum_hosp'], 2);

            $positivite[]   = (empty($res['sum_T'])) ?
                'null' : round((100 / $res['sum_T'] * $res['sum_P']), 2);
        }

        $jours      = implode(', ', $jours);
        $hosp       = implode(', ', $hosp);
        $positivite = implode(', ', $positivite);

        $credit     = HighChartsCommon::creditLCH();
        $event      = HighChartsCommon::exportImgLogo(true);
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

            yAxis: [{ // Primary yAxis
                title: {
                    text: '{$this->yAxis1Label}',
                    style: {
                        color: '#106097',
                        fontSize: 14
                    }
                },
                labels: {
                    format: '{value:.2f}%',
                    allowDecimals: 2,
                    style: {
                        color: '#106097',
                        fontSize: 14
                    }
                }

            }, { // Secondary yAxis
                title: {
                    text: '{$this->yAxis2Label}',
                    style: {
                        color: '#c70000',
                        fontSize: 14
                    }
                },
                labels: {
                    format: '{value}',
                    style: {
                        color: '#c70000',
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
                data: [$positivite]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis2Label}',
                color: '#c70000',
                yAxis: 1,
                data: [$hosp]
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
            'charts'    => [true, 'col-lg-5'],
            'region'    => [true, 'col-lg-4'],
            'interval'  => [true, 'col-lg-3'],
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
