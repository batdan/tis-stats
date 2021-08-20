<?php
namespace spf\charts;

use tools\dbSingleton;

class quotidienRad
{
    private $cache;
    private $dbh;

    private $chartName;

    private $title;
    private $subTitle;

    private $yAxis1Label;
    private $yAxis2Label;
    private $yAxis3Label;
    private $yAxis4Label;

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

        $this->chartName = 'quotidienRad';

        $this->title    = 'Retours à domicile covid-19 | Décès covid-19 | Hospitalisations covid-19 | Entrées soins critiques covid-19';
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours)';

        $this->yAxis1Label = 'Retours à domicile';
        $this->yAxis2Label = 'Décès';
        $this->yAxis3Label = 'hospitalisations';
        $this->yAxis4Label = 'Entrées en soins critiques';

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
                            SUM(rad)  AS sum_rad,
                            SUM(dc)   AS sum_dc,
                            SUM(hosp) AS sum_hosp,
                            SUM(rea)  AS sum_rea

                FROM        donnees_hp_quotidien_covid19_reg_calc_lisse7j

                WHERE       1 $addReq

                GROUP BY    jour
                ORDER BY    jour ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->jour] = [
                'sum_rad'   => $res->sum_rad,
                'sum_dc'    => $res->sum_dc,
                'sum_hosp'  => $res->sum_hosp,
                'sum_rea'   => $res->sum_rea,
            ];
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
        $rad        = [];
        $dc         = [];
        $hosp       = [];
        $rea        = [];

        foreach($this->data as $jour => $res) {
            $jours[]    = "'".$jour."'";
            $rad[]      = round($res['sum_rad'],  2);
            $dc[]       = round($res['sum_dc'],   2);
            $hosp[]     = round($res['sum_hosp'], 2);
            $rea[]      = round($res['sum_rea'],  2);
        }

        $jours  = implode(', ', $jours);
        $rad    = implode(', ', $rad);
        $dc     = implode(', ', $dc);
        $hosp   = implode(', ', $hosp);
        $rea    = implode(', ', $rea);

        $this->highChartsJs = <<<eof
        Highcharts.chart('{$this->chartName}', {
            credits: {
                enabled: false,
            },

            chart: {
                type: 'spline',
                height: 600
            },

            title: {
                text: '{$this->title}'
            },

            subtitle: {
                text: '{$this->subTitle}'
            },

            yAxis: [{ // Primary yAxis
                title: {
                    text: 'Nombre de personnes',
                    style: {
                        color: '#106097',
                        fontSize: 14
                    }
                },
                labels: {
                    format: '{value}',
                    allowDecimals: 2,
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
                data: [$rad]
            }, {
                name: '{$this->yAxis2Label}',
                color: '#ff891a',
                data: [$dc]
            }, {
                name: '{$this->yAxis3Label}',
                color: '#b00000',
                data: [$hosp]
            }, {
                name: '{$this->yAxis4Label}',
                // color: '#c70000',
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
