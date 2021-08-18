<?php
namespace spf\charts;

use tools\dbSingleton;


/**
 * Nombre quotidien de vaccinés par age
 */
class nbCumuleVaccinationAge
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

        $this->chartName = 'nbCumuleVaccinationAge';

        $this->title    = 'Nb cumulé de vaccinés covid-19 par age';
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (quotidien, lissé sur 7 jours)';

        $this->yAxis1Label = 'Nb cumulé de vaccinés 1ère dose';
        $this->yAxis2Label = 'Nb cumulé de vaccinés 2ème dose';

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
        if (!empty($_SESSION['filterRegionId']) && is_numeric($_SESSION['filterRegionId'])) {
            $addReq .= " AND reg = :reg";
            $addReqValues[':reg'] = $_SESSION['filterRegionId'];
            $fileName .= '_reg_' . $_SESSION['filterRegionId'];
        }

        if (!empty($_SESSION['filterInterval']) && $_SESSION['filterInterval'] != 'all') {
            $addReq .= " AND jour >= :jour";
            $addReqValues[':jour'] = $_SESSION['filterInterval'];
            $fileName .= '_interval_' . $_SESSION['filterInterval'];
        }

        $addReq .= " AND clage_vacsi = :clage_vacsi";
        if (!empty($_SESSION['filterAge2']) && $_SESSION['filterAge2'] != '0') {
            $addReqValues[':clage_vacsi'] = $_SESSION['filterAge2'];
            $fileName .= '_age_' . $_SESSION['filterAge2'];
        } else {
            $addReqValues[':clage_vacsi'] = 0;
        }

        if ($this->cache && $this->data = \spf\cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        $req = "SELECT      jour,
                            SUM(n_cum_dose1) AS sum_n_cum_dose1,
                            SUM(n_cum_dose2) AS sum_n_cum_dose2

                FROM        donnees_vaccination_age_covid19_calc_lisse7j

                WHERE       1 $addReq

                GROUP BY    jour
                ORDER BY    jour ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->jour] = [
                'sum_n_cum_dose1' => $res->sum_n_cum_dose1,
                'sum_n_cum_dose2' => $res->sum_n_cum_dose2,
            ];
        }

        // createCache
        \spf\cache::createCache($fileName, $this->data);
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $jours       = [];
        $n_cum_dose1 = [];
        $n_cum_dose2 = [];

        foreach($this->data as $jour => $res) {
            $jours[] = "'".$jour."'";
            $n_cum_dose1[] = $res['sum_n_cum_dose1'];
            $n_cum_dose2[] = $res['sum_n_cum_dose2'];
        }

        $jours       = implode(', ', $jours);
        $n_cum_dose1 = implode(', ', $n_cum_dose1);
        $n_cum_dose2 = implode(', ', $n_cum_dose2);

        $this->highChartsJs = <<<eof
        Highcharts.chart('{$this->chartName}', {
            credits: {
                enabled: false
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
                    text: 'Nombre cumulé de vaccinés',
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
                name: '{$this->yAxis1Label}',
                color: '#106097',
                // type: 'spline',
                yAxis: 0,
                data: [$n_cum_dose1]
            }, {
                name: '{$this->yAxis2Label}',
                color: '#c70000',
                // type: 'spline',
                yAxis: 0,
                data: [$n_cum_dose2]
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
        $this->title .= ($_SESSION['filterRegionId'] == 0) ? ' | ' . $_SESSION['filterRegionName'] : ' | Région : ' . $_SESSION['filterRegionName'];
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
            'age2'      => true,
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
