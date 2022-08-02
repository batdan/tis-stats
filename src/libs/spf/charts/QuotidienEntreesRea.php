<?php

namespace spf\charts;

use tools\dbSingleton;
use main\HighChartsCommon;
use main\Cache;
use DateTime;

class QuotidienEntreesRea
{
    private $cache;
    private $dbh;

    private $chartName;
    private $measures;

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

        $this->chartName = 'QuotidienEntreesRea';
        $this->title    = "Nombre quotidien d'entrées en soins critiques covid-19 | Taux de positivité covid-19";
        $this->title    = HighChartsCommon::chartText($this->title);
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours) | Données hospitalières';

        $this->yAxis1Label = '% de positifs sur la population testée';
        $this->yAxis2Label = "Nb quotidien d'entrées en soins critiques";
        $this->yAxis2Label = HighChartsCommon::chartText($this->yAxis2Label);

        $this->getData();
        $this->getMeasures();
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
                            SUM(rea) AS sum_rea

                FROM        donnees_hp_quotidien_covid19_reg_calc_lisse7j

                WHERE       1 $addReq

                GROUP BY    jour
                ORDER BY    jour ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->jour]['sum_rea'] = $res->sum_rea;
        }

        ksort($this->data);

        foreach ($this->data as $k => $v) {
            $this->data[$k]['sum_T']    = (!isset($v['sum_T']))     ? null : $v['sum_T'];
            $this->data[$k]['sum_P']    = (!isset($v['sum_P']))     ? null : $v['sum_P'];
            $this->data[$k]['sum_rea']  = (!isset($v['sum_rea']))   ? null : $v['sum_rea'];
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
            ':iso_3166_1_alpha_2'   => 'FR',
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
        $jours      = [];
        $rea        = [];
        $positivite = [];

        foreach ($this->data as $jour => $res) {
            if (!isset($dateDeb_Y)) {
                $expDateDeb = explode('-', $jour);
                $dateDeb_Y  = $expDateDeb[0];
                $dateDeb_m  = intval($expDateDeb[1]) - 1;
                $dateDeb_d  = intval($expDateDeb[2]);
            }

            $jours[] = "'" . $jour . "'";
            $rea[]  = round($res['sum_rea'], 2);

            $positivite[]   = (empty($res['sum_T'])) ?
                'null' : round((100 / $res['sum_T'] * $res['sum_P']), 2);
        }

        $jours      = implode(', ', $jours);
        $rea        = implode(', ', $rea);
        $positivite = implode(', ', $positivite);

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

        $credit     = HighChartsCommon::creditLCH();
        $event      = HighChartsCommon::exportImgLogo(true);
        $xAxis      = HighChartsCommon::xAxis([], false, $plotDands);
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
                data: [$rea]
            }],

            plotOptions: {
                series: {
                    pointStart: Date.UTC($dateDeb_Y, $dateDeb_m, $dateDeb_d),
                    pointInterval: 24 * 3600 * 1000     // One day
                }
            },

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
