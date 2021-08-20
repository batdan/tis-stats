<?php
namespace owid\charts;

use tools\dbSingleton;

class totalDeathPerMillion
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

        $this->chartName = 'nbCumuleDeathPerMillion';

        $this->title    = 'Nb cumulé de décès covid-19 par millions d`habitants';
        $this->subTitle = 'Source: Our World in Data (lissé)';

        $this->yAxis1Label = 'Nb cumulé de décès';

        $this->getCountries();

        $this->getData();
        $this->highChartsJs();
    }


    private function getCountries()
    {
        $req = "SELECT ISO, location FROM owid_covid19 WHERE activ = 1";
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

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        foreach($this->countries as $iso => $country) {

            $tableCountry = 'owid_covid19_' . $iso;

            $req = "SELECT      jour,
                                total_deaths_per_million

            FROM        $tableCountry

            WHERE       1 $addReq

            ORDER BY    jour ASC";

            $sql = $this->dbh->prepare($req);
            $sql->execute($addReqValues);

            while ($res = $sql->fetch()) {
                $this->data[$res->jour][$iso] = [
                    'total_deaths_per_million' => $res->total_deaths_per_million,
                ];
            }
        }

        ksort($this->data);

        // clean data
        foreach ($this->data as $jour => $res) {
            $sum = 0;
            foreach($this->countries as $iso => $country) {
                $sum += floatval($res[$iso]['total_deaths_per_million']);
            }

            if ($sum == 0) {
                unset($this->data[$jour]);
            }
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
        $countriesSerie = [];

        foreach($this->data as $jour => $res) {
            $jours[] = "'".$jour."'";

            foreach($this->countries as $iso => $country) {
                $tdpm = floatval($res[$iso]['total_deaths_per_million']);
                $countriesSerie[$iso][] = !empty($tdpm) ? $tdpm : "'NULL'";
            }
        }

        $jours = implode(',', $jours);

        $series = [];
        foreach($this->countries as $iso => $country) {
            $serieCountry = implode(',', $countriesSerie[$iso]);
            $series[] = <<<eof
            {
                name: '$country',
                data: [$serieCountry]
            }
eof;
        }

        $series = implode(', ', $series);

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

            yAxis: [{
                title: {
                    text: '{$this->yAxis1Label}',
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

            series: [$series],

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


    /**
     * Redu HTML
     */
    public function render()
    {
        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [
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
