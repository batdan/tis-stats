<?php
namespace spf\maps;

use tools\dbSingleton;
use main\highChartsCommon;

class totalFirstVaccinatedPerHundred
{
    private $cache;
    private $dbh;

    private $regions;

    private $chartName;

    private $title;
    private $subTitle;

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

        $this->chartName = 'totalFirstVaccinatedPerHundred';
        $this->title     = "Pourcentage de la population partiellement vaccinée covid-19";
        $this->subTitle  = 'Source: Santé Publique France | Vaccination';
        $this->legend    = "Pourcentage partiellement vaccinée";
    }


    private function getRegions()
    {
        $req = "SELECT region, iso, population FROM geo_reg2018";
        $sql = $this->dbh->query($req);

        $this->regions = [];
        while ($res = $sql->fetch()) {
            $this->regions[$res->region] = [
                'iso'        => $res->iso,
                'population' => $res->population,
            ];
        }
    }


    /**
     * Récupération des données de la statistique
     * en cache ou en BDD
     */
    public function getData()
    {
        $this->getRegions();

        $className = str_replace('\\', '_', get_class($this));
        $fileName  = date('Y-m-d_') . $className;

        $addReq = "";
        $addReqValues = [];
        $addReq .= " AND clage_vacsi = :clage_vacsi";
        if (!empty($_SESSION['spf_filterMapAge2']) && $_SESSION['spf_filterMapAge2'] != '0') {
            $addReqValues[':clage_vacsi'] = $_SESSION['spf_filterMapAge2'];
            $fileName .= '_age_' . $_SESSION['spf_filterMapAge2'];
        } else {
            $addReqValues[':clage_vacsi'] = 0;
        }

        // if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
        //     return json_encode($this->data);
        // }

        $this->data = [];

        $req = "SELECT      reg, SUM(n_cum_dose1) AS mySum

                FROM        donnees_vaccination_age_covid19

                WHERE       jour = (
                                SELECT      jour
                                FROM        donnees_vaccination_age_covid19
                                ORDER BY    jour DESC
                                LIMIT       1
                            )

                $addReq

                GROUP BY    reg
                ORDER BY    reg ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);
        $results = $sql->fetchAll();

        foreach ($results as $res) {
            if (isset($this->regions[$res->reg]['iso'])) {
                $this->data[] = [
                    $this->regions[$res->reg]['iso'],
                    (100 / intval($this->regions[$res->reg]['population']) * floatval($res->mySum))
                ];
            }
        }

        // createCache
        if ($this->cache) {
            \main\cache::createCache($fileName, $this->data);
        }

        return json_encode($this->data);
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $credit = highChartsCommon::creditMapsLCH();

        $this->highChartsJs = <<<eof
            Highcharts.getJSON('/charts/spf/covid19-maps-json.php?{$this->chartName}', function (data) {

                // Prevent logarithmic errors in color calulcation
                data.forEach(function (p) {
                    p.value = (p.value < 1 ? 1 : p.value);
                });

                Highcharts.mapChart('{$this->chartName}', {

                    $credit

                    chart: {
                        map: 'countries/fr/fr-all'
                    },

                    title: {
                        text: '{$this->title}'
                    },

                    subtitle: {
                        text: '{$this->subTitle}'
                    },

                    mapNavigation: {
                        enabled: true,
                        buttonOptions: {
                            verticalAlign: 'bottom'
                        }
                    },

                    colorAxis: {
                        // min: 1,
                        // max: 200
                        // type: 'logarithmic'
                    },

                    tooltip: {
                        valueDecimals: 2,
                        valueSuffix: '%'
                    },

                    series: [{
                        data: data,
                        name: '{$this->legend}',
                        states: {
                            hover: {
                                color: '#BADA55'
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            format: '{point.name}'
                        }
                    }, {
                        name: 'Separators',
                        type: 'mapline',
                        // data: Highcharts.geojson(Highcharts.maps['countries/fr/fr-all'], 'mapline'),
                        data: data,
                        color: 'silver',
                        nullColor: 'silver',
                        showInLegend: false,
                        enableMouseTracking: false
                    }]
                });
            });
eof;
    }


    /**
     * Redu HTML
     */
    public function render()
    {
        $this->highChartsJs();

        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [
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
