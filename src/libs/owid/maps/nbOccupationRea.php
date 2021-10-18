<?php
/**
 * Accès à la collection de cartes disponibles pour highcharts
 * https://code.highcharts.com/mapdata/
 */
namespace owid\maps;

use tools\dbSingleton;
use main\highChartsCommon;

class nbOccupationRea
{
    private $cache;
    private $dbh;

    private $countries;

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

        $this->chartName = 'nbOccupationRea';

        $this->title    = "Nb actuel de patients covid-19 en soins critiques par million d'habitants";
        $this->title    = highChartsCommon::chartText($this->title);

        $this->legend   = "Patients en soins critiques par million d'habitant";
        $this->legend   = highChartsCommon::chartText($this->legend);

        $this->legend2  = "Nb soins critiques par million";

        $this->subTitle = 'Source: Our World in Data';
    }


    private function getCountries()
    {
        $req = "SELECT ISO, location FROM owid_covid19";
        $sql = $this->dbh->query($req);

        $this->countries = [];
        while ($res = $sql->fetch()) {
            if (strlen($res->ISO) == 3) {
                $this->countries[$res->ISO] = $res->location;
            }
        }
    }


    /**
     * Récupération des données de la statistique
     * en cache ou en BDD
     */
    public function getData()
    {
        $this->getCountries();

        $className = str_replace('\\', '_', get_class($this));
        $fileName  = date('Y-m-d_') . $className;


        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
            return json_encode($this->data);
        }

        $this->data = [];

        foreach($this->countries as $iso => $country) {

            $tableCountry = 'owid_covid19_' . $iso;

            $req = "SELECT      jour, icu_patients_per_million AS myVal
                    FROM        $tableCountry
                    WHERE       icu_patients_per_million > 0
                    ORDER BY    jour DESC
                    LIMIT       0,1";

            $sql = $this->dbh->query($req);

            while ($res = $sql->fetch()) {
                $this->data[] = [
                    'code3' => $iso,
                    'name'  => $country,
                    'value' => round($res->myVal),
                    'jour'  => $res->jour
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
            Highcharts.getJSON('/charts/owid/covid19-maps-json.php?{$this->chartName}', function (data) {

                // Prevent logarithmic errors in color calulcation
                data.forEach(function (p) {
                    p.value = (p.value < 1 ? 1 : p.value);
                });

                // Initiate the chart
                Highcharts.mapChart('{$this->chartName}', {

                    $credit

                    chart: {
                        map: 'custom/world'
                    },

                    title: {
                        text: '{$this->title}'
                    },

                    subtitle: {
                        text: '{$this->subTitle}'
                    },

                    legend: {
                        title: {
                            text: '{$this->legend}',
                            style: {
                                color: ( // theme
                                    Highcharts.defaultOptions &&
                                    Highcharts.defaultOptions.legend &&
                                    Highcharts.defaultOptions.legend.title &&
                                    Highcharts.defaultOptions.legend.title.style &&
                                    Highcharts.defaultOptions.legend.title.style.color
                                ) || 'black'
                            }
                        }
                    },

                    mapNavigation: {
                        enabled: true,
                        buttonOptions: {
                            verticalAlign: 'bottom'
                        }
                    },

                    tooltip: {
                        backgroundColor: 'rgba(255,255,255,0.7)',
                        borderWidth: 0,
                        shadow: false,
                        useHTML: true,
                        padding: 0,
                        pointFormat: '<span class="f32"><span class="flag {point.properties.hc-key}">' +
                            '</span></span> {point.name}<br>' +
                            '<div style="font-size:14px; margin-top:10px;">Le {point.jour} :</div><br>' +
                            '<span style="font-size:30px">{point.value}</span><br>',
                        positioner: function () {
                            return { x: 0, y: 250 };
                        }
                    },

                    colorAxis: {
                        min: 1,
                        max: 100,
                        // type: 'logarithmic'
                    },

                    series: [{
                        data: data,
                        joinBy: ['iso-a3', 'code3'],
                        name: '{$this->legend2}',
                        states: {
                            hover: {
                                color: '#a4edba'
                            }
                        }
                    }],
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

        $filterActiv = [];

        echo render::html(
            $this->chartName,
            $this->title,
            $this->highChartsJs,
            $backLink,
            $filterActiv
        );
    }
}
