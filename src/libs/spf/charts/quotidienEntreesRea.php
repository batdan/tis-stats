<?php
namespace spf\charts;

use tools\dbSingleton;
use main\highChartsCommon;

class quotidienEntreesRea
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

        $this->chartName = 'quotidienEntreesRea';
        $this->title    = "Nombre quotidien d'entrées en soins critiques covid-19 | Taux de positivité covid-19";
        $this->title    = highChartsCommon::chartText($this->title);
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours)';

        $this->yAxis1Label = '% de positifs sur la population testée';
        $this->yAxis2Label = "Nb quotidien d'entrées en soins critiques";
        $this->yAxis2Label = highChartsCommon::chartText($this->yAxis2Label);

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

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
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

        foreach($this->data as $k => $v) {
            $this->data[$k]['sum_T']    = (!isset($v['sum_T']))     ? null : $v['sum_T'];
            $this->data[$k]['sum_P']    = (!isset($v['sum_P']))     ? null : $v['sum_P'];
            $this->data[$k]['sum_rea']  = (!isset($v['sum_rea']))   ? null : $v['sum_rea'];
        }

        // createCache
        \main\cache::createCache($fileName, $this->data);
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $jours      = [];
        $rea        = [];
        $positivite = [];

        foreach($this->data as $jour => $res) {
            $jours[] = "'".$jour."'";
            $rea[]  = round($res['sum_rea'], 2);

            $positivite[]   = (empty($res['sum_T'])) ?
                'null' : round((100 / $res['sum_T'] * $res['sum_P']), 2);
        }

        $jours      = implode(', ', $jours);
        $rea        = implode(', ', $rea);
        $positivite = implode(', ', $positivite);

        $credit = highChartsCommon::imgLogo();
        $event  = highChartsCommon::exportImgLogo(true);

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

            xAxis: {
                categories: [$jours],
                labels: {
                    style: {
                        fontSize: 12
                    }
                }
            },

            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle'
            },

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

            responsive: {
                rules: [{
                    condition: {
                        maxWidth: 1900
                    },
                    chartOptions: {
                        legend: {
                            layout: 'horizontal',
                            align: 'center',
                            verticalAlign: 'bottom'
                        }
                    }
                }]
            }
        });
        eof;
    }


    private function regTitle()
    {
        $this->title .= ($_SESSION['spf_filterRegionId'] == 0)
                            ? ' | ' . $_SESSION['spf_filterRegionName']
                            : ' | Région : ' . highChartsCommon::chartText($_SESSION['spf_filterRegionName']);
    }


    /**
     * Redu HTML
     */
    public function render()
    {
        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [
            'region'    => true,
            'interval'  => true,
        ];

        echo render::html(
            $this->chartName,
            $this->title,
            $this->highChartsJs,
            $backLink,
            $filterActiv
        );
    }
}
