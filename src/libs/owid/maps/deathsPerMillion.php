<?php
namespace owid\maps;

use tools\dbSingleton;
use main\highChartsCommon;

class deathsPerMillion
{
    private $cache;
    private $dbh;

    private $countries;

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

        $this->chartName = 'deathsPerMillion';

        $this->title    = "Nb actuel de décès par million d'habitants";
        $this->title    = highChartsCommon::chartText($this->title);

        $this->subTitle = 'Source: Our World in Data';

        // $this->getCountries();
        //
        // $this->getData();
        $this->highChartsJs();
    }


    private function getCountries()
    {
        $isoList = [];
        foreach ($_SESSION['owid_filterCountry'] as $iso) {
            $isoList[] = "'" . $iso . "'";
        }
        $isoList = implode(',', $isoList);

        $req = "SELECT ISO, location FROM owid_covid19 WHERE ISO IN (" . $isoList . ")";
        $sql = $this->dbh->query($req);

        $this->countries = [];
        while ($res = $sql->fetch()) {
            $this->countries[$res->ISO] = $res->location;
        }
    }


    /**
     * Récupération des données de la statistique
     * en cache ou en BDD
     */
    private function getData()
    {
        $className = str_replace('\\', '_', get_class($this));
        $fileName  = date('Y-m-d_') . $className;

        $addReq = "";
        $addReqValues = [];

        if (!empty($_SESSION['owid_filterInterval']) && $_SESSION['owid_filterInterval'] != 'all') {
            $addReq .= " AND jour >= :jour";
            $addReqValues[':jour'] = $_SESSION['owid_filterInterval'];
            $fileName .= '_interval_' . $_SESSION['owid_filterInterval'];
        }

        $fileName .= '_' . implode('_', $_SESSION['owid_filterCountry']);

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        foreach($this->countries as $iso => $country) {

            $tableCountry = 'owid_covid19_' . $iso;

            $req = "SELECT      jour,
                                hosp_patients_per_million AS myVal

            FROM        $tableCountry

            WHERE       1 $addReq

            ORDER BY    jour ASC";

            $sql = $this->dbh->prepare($req);
            $sql->execute($addReqValues);

            while ($res = $sql->fetch()) {
                $this->data[$res->jour][$iso] = [
                    '__VAL__' => $res->myVal,
                ];
            }
        }

        $this->cleanData();

        // createCache
        \main\cache::createCache($fileName, $this->data);
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $this->highChartsJs = <<<eof
            Highcharts.getJSON('https://cdn.jsdelivr.net/gh/highcharts/highcharts@v7.0.0/samples/data/world-population-density.json', function (data) {

                // Prevent logarithmic errors in color calulcation
                data.forEach(function (p) {
                    p.value = (p.value < 1 ? 1 : p.value);
                });

                // Initiate the chart
                Highcharts.mapChart('{$this->chartName}', {

                    chart: {
                        map: 'custom/world'
                    },

                    title: {
                        text: 'Fixed tooltip with HTML'
                    },

                    legend: {
                        title: {
                            text: 'Population density per km²',
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
                        backgroundColor: 'none',
                        borderWidth: 0,
                        shadow: false,
                        useHTML: true,
                        padding: 0,
                        pointFormat: '<span class="f32"><span class="flag {point.properties.hc-key}">' +
                            '</span></span> {point.name}<br>' +
                            '<span style="font-size:30px">{point.value}/km²</span>',
                        positioner: function () {
                            return { x: 0, y: 250 };
                        }
                    },

                    colorAxis: {
                        min: 1,
                        max: 100,
                        type: 'logarithmic'
                    },

                    series: [{
                        data: data,
                        joinBy: ['iso-a3', 'code3'],
                        name: 'Population density',
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
