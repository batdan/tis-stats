<?php
namespace spf\charts;

use tools\dbSingleton;

class nbCumuleRad
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

        $this->chartName = 'nbCumuleRad';

        $this->title    = 'Nb cumulé des retours à domicile Covid19 | Taux de positivité covid-19';
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours)';

        $this->yAxis1Label = 'Retours à domicile covid-19';
        $this->yAxis2Label = 'Décès covid-19';

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

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        $req = "SELECT      jour,
                            SUM(rad)  AS sum_rad,
                            SUM(dc)   AS sum_dc

                FROM        donnees_hp_cumule_age_covid19_reg_calc_lisse7j

                WHERE       1 $addReq

                GROUP BY    jour
                ORDER BY    jour ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->jour]['sum_rad']  = (!isset($res->sum_rad))  ? null : $res->sum_rad;
            $this->data[$res->jour]['sum_dc']   = (!isset($res->sum_dc))   ? null : $res->sum_dc;
        }

        // createCache
        \main\cache::createCache($fileName, $this->data);
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $jours = [];
        $rad   = [];
        $dc    = [];

        foreach($this->data as $jour => $res) {
            $jours[] = "'".$jour."'";
            $rad[]  = round($res['sum_rad'],  2);
            $dc[]   = round($res['sum_dc'],   2);
        }

        $jours  = implode(', ', $jours);
        $rad    = implode(', ', $rad);
        $dc     = implode(', ', $dc);

        $this->highChartsJs = <<<eof
        Highcharts.chart('{$this->chartName}', {
            credits: {
                enabled: false
            },

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
                    text: 'Nombre de personnes',
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
                }
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
                name: '{$this->yAxis1Label}',
                color: '#0e8042',
                // yAxis: 0,
                data: [$rad]
            }, {
                name: '{$this->yAxis2Label}',
                color: '#ff891a',
                // yAxis: 0,
                data: [$dc]
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
        $this->title .= ($_SESSION['spf_filterRegionId'] == 0) ? ' | ' . $_SESSION['spf_filterRegionName'] : ' | Région : ' . $_SESSION['spf_filterRegionName'];
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
            'age'       => true,
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
