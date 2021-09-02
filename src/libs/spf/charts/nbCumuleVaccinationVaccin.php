<?php
namespace spf\charts;

use tools\dbSingleton;
use main\highChartsCommon;

/**
 * Nombre quotidien de vaccinés par vaccin
 */
class nbCumuleVaccinationVaccin
{
    private $cache;
    private $dbh;

    private $chartName;

    private $title;
    private $subTitle;

    private $yAxis1Label;
    private $yAxis2Label;

    private $regions;
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

        $this->chartName = 'nbCumuleVaccinationVaccin';

        $this->title    = 'Pourcentage de vaccinés covid-19 par vaccin';
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours) | Vaccination';

        $this->yAxis1Label = 'Pourcentage de vaccinés 1ère dose';
        $this->yAxis2Label = 'Pourcentage de vaccinés 2ème dose';

        $this->getRegions();
        $this->getData();
        $this->highChartsJs();
    }


    private function getRegions()
    {
        $req = "SELECT region, iso, population FROM geo_reg2018";
        $sql = $this->dbh->query($req);

        // Préenregistrement de la population en France
        $this->regions = [0 => 67394862];
        while ($res = $sql->fetch()) {
            $this->regions[$res->region] = $res->population;
        }
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

        $addReq .= " AND vaccin = :vaccin";
        if (!empty($_SESSION['spf_filterVaccin']) && $_SESSION['spf_filterVaccin'] != '0') {
            $addReqValues[':vaccin'] = $_SESSION['spf_filterVaccin'];
            $fileName .= '_vaccin_' . $_SESSION['spf_filterVaccin'];
        } else {
            $addReqValues[':vaccin'] = 0;
        }

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        $req = "SELECT      jour,
                            SUM(n_cum_dose1) AS sum_n_cum_dose1,
                            SUM(n_cum_dose2) AS sum_n_cum_dose2

                FROM        donnees_vaccination_vaccin_covid19_calc_lisse7j

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
        \main\cache::createCache($fileName, $this->data);
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
            $n_cum_dose1[] = 100 / $this->regions[$_SESSION['spf_filterRegionId']] * $res['sum_n_cum_dose1'];
            $n_cum_dose2[] = 100 / $this->regions[$_SESSION['spf_filterRegionId']] * $res['sum_n_cum_dose2'];
        }

        $jours       = implode(', ', $jours);
        $n_cum_dose1 = implode(', ', $n_cum_dose1);
        $n_cum_dose2 = implode(', ', $n_cum_dose2);

        $credit = highChartsCommon::creditLCH();
        $event  = highChartsCommon::exportImgLogo();

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
                    text: 'Pourcentage de vaccinés',
                    style: {
                        color: '#c70000',
                        fontSize: 14
                    }
                },
                labels: {
                    format: '{value:.2f}%',
                    allowDecimals: 2,
                    style: {
                        color: '#c70000',
                        fontSize: 14
                    }
                },
                opposite: true
            }],

            xAxis: {
                categories: [$jours],
                type: 'datetime',
                dateTimeLabelFormats: {
                    week: '%e of %b'
                },
                labels: {
                    format: '{value:%Y-%m-%d}',
                    rotation: -45,
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

            tooltip: {
                valueDecimals: 2,
                valueSuffix: '%'
            },

            series: [{
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis1Label}',
                color: '#106097',
                yAxis: 0,
                data: [$n_cum_dose1]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis2Label}',
                color: '#c70000',
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
            'vaccin'    => true,
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
