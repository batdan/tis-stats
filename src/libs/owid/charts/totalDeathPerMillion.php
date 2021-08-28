<?php
namespace owid\charts;

use tools\dbSingleton;
use main\highChartsCommon;

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

        $this->chartName = 'totalDeathPerMillion';

        $this->title    = "Nb cumulé de décès covid-19 par million d'habitants";
        $this->title    = highChartsCommon::chartText($this->title);

        $this->subTitle = 'Source: Our World in Data';

        $this->yAxis1Label = "Nb cumulé de décès pour un million d'habitants";
        $this->yAxis1Label = highChartsCommon::chartText($this->yAxis1Label);

        $this->getCountries();

        $this->getData();
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
                                total_deaths_per_million AS myVal

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
        $jours = [];
        $countriesSerie = [];

        foreach($this->data as $jour => $res) {
            $jours[] = "'".$jour."'";

            foreach($this->countries as $iso => $country) {
                if (isset($res[$iso]['__VAL__'])) {
                    $tdpm = floatval($res[$iso]['__VAL__']);
                    if ($tdpm < 0) $tdpm = 0;
                    $countriesSerie[$iso][] = !empty($tdpm) ? $tdpm : "'NULL'";
                }
            }
        }

        $jours = implode(',', $jours);

        $series = [];
        foreach($this->countries as $iso => $country) {
            $serieCountry = implode(',', $countriesSerie[$iso]);
            $series[] = <<<eof
            {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '$country',
                data: [$serieCountry]
            }
eof;
        }

        $series = implode(', ', $series);

        $event = highChartsCommon::exportImgLogo();

        $this->highChartsJs = <<<eof
        Highcharts.chart('{$this->chartName}', {
            credits: {
                enabled: false
            },

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


    private function cleanData()
    {
        ksort($this->data);

        $lastDayValIso = [];

        foreach ($this->data as $jour => $res) {
            $sum = 0;
            foreach($this->countries as $iso => $country) {

                if (isset($res[$iso]['__VAL__'])) {
                    $val = floatval($res[$iso]['__VAL__']);

                    // On vérifie s'il existe des données pour ce jour
                    $sum += floatval($res[$iso]['__VAL__']);
                }
            }

            if ($sum == 0) {
                unset($this->data[$jour]);
            }
        }
    }


    /**
     * Redu HTML
     */
    public function render()
    {
        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [
            'country'   => true,
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
