<?php
namespace eurostat\charts;

use tools\dbSingleton;
use main\highChartsCommon;
use eurostat\main\tools;

class pyramideAges
{
    private $cache;
    private $dbh;

    private $chartName;

    private $title;
    private $subTitle;

    private $yAxisLabel;

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

        $this->chartName = 'pyramideAges';

        $this->title    = 'Pyramide des âges';
        $this->regTitle();

        $maj = tools::lastMajData();
        $this->subTitle = (!empty($maj)) ? 'Source: Eurostats | ' . $maj : 'Source: Eurostats';

        $this->yAxisLabel = 'Population';

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
        if (!empty($_SESSION['eurostat_filterCountry'])) {
            $addReq .= " AND geotime = :geotime";
            $addReqValues[':geotime'] = $_SESSION['eurostat_filterCountry'];
            $fileName .= '_country_' . $_SESSION['eurostat_filterCountry'];
        }

        if (!empty($_SESSION['eurostat_filterYear1'])) {
            $addReq .= " AND year = :year";
            $addReqValues[':year'] = $_SESSION['eurostat_filterYear1'];
            $fileName .= '_year_' . $_SESSION['eurostat_filterYear1'];
        }

        if (!empty($_SESSION['eurostat_filterUnit'])) {
            $fileName .= '_unit_' . $_SESSION['eurostat_filterUnit'];
        }

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        // Récupératation de la population du pays sur l'année analysée
        $req = "SELECT      value
                FROM        eurostat_demo_pjan_opti
                WHERE       sex = 'T'
                $addReq
                AND         age = 'TOTAL'
                GROUP BY    age";
        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);
        $res = $sql->fetch();
        $population = $res->value;

        // Données sur les femmes
        $req = "SELECT      age, value
                FROM        eurostat_demo_pjan_opti
                WHERE       sex = 'F'
                $addReq
                AND         age != 'TOTAL'
                GROUP BY    age";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data['F_pre'][$res->age] = $res->value;
        }

        // Données sur les hommes
        $req = "SELECT      age, value
                FROM        eurostat_demo_pjan_opti
                WHERE       sex = 'M'
                $addReq
                AND         age != 'TOTAL'
                GROUP BY    age";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data['M_pre'][$res->age] = $res->value;
        }

        // Remise du tableau dans l'ordre et Calculs
        $this->data['F'] = [];
        $this->data['M'] = [];
        foreach (array_keys(tools::rangeFilterAge2()) as $key) {
            if ($_SESSION['eurostat_filterUnit'] == 'percent') {
                $this->data['F'][$key] = 100 / $population * $this->data['F_pre'][$key];
                $this->data['M'][$key] = 0 - (100 / $population * $this->data['M_pre'][$key]);
            } else {
                $this->data['F'][$key] = $this->data['F_pre'][$key];
                $this->data['M'][$key] = 0 - $this->data['M_pre'][$key];
            }
        }

        // Clean result
        unset($this->data['F_pre']);
        unset($this->data['M_pre']);

        // createCache
        if ($this->cache) {
            \main\cache::createCache($fileName, $this->data);
        }
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $ages = tools::rangeFilterAge2('format');

        $femmes = [];
        $hommes = [];

        foreach($this->data['F'] as $val) {
            $femmes[] = $val;
        }
        $femmes = implode(',', $femmes);

        foreach($this->data['M'] as $val) {
            $hommes[] = $val;
        }
        $hommes = implode(',', $hommes);

        $credit     = highChartsCommon::creditLCH(-300, 70);
        $event      = highChartsCommon::exportImgLogo();
        $legend     = highChartsCommon::legend('bottom');
        $responsive = highChartsCommon::responsive();

        $barsColor  = tools::getSexColor();
        $barsColorM = $barsColor['M'];
        $barsColorF = $barsColor['F'];

        switch ($_SESSION['eurostat_filterUnit'])
        {
            case 'percent':
                $labelFormatter = <<<eof
                    return Math.abs(this.value) + '%';
eof;
                break;
            default:
                $labelFormatter = <<<eof
                    var number = Math.abs(this.value);
                    return Highcharts.numberFormat(number, 0, '.', ' ');
eof;
        }

        $this->highChartsJs = <<<eof
        var categories = [$ages];

        Highcharts.chart('{$this->chartName}', {

            $credit

            $event

            chart: {
                type: 'bar',
                height: 680
            },

            title: {
                text: '{$this->title}'
            },

            subtitle: {
                text: '{$this->subTitle}'
            },

            accessibility: {
                point: {
                    valueDescriptionFormat: '{index}. Age {xDescription}, {value}%.'
                }
            },

            yAxis: {
                title: {
                    text: ''
                },
                labels: {
                    formatter: function() {
                        $labelFormatter
                    },
                    rotation: -45,
                    style: {
                        fontSize: 12,
                        color: '#222'
                    }
                },
                accessibility: {
                    description: 'Percentage population',
                    rangeDescription: 'Range: 0 to 5%'
                }
            },

            xAxis: [{
                categories: categories,
                reversed: false,
                labels: {
                    step: 1,
                    style: {
                        fontSize: 13,
                        color: '#222'
                    }
                },
                accessibility: {
                    description: 'Age (male)'
                }
            }, { // mirror axis on right side
                opposite: true,
                reversed: false,
                categories: categories,
                linkedTo: 0,
                labels: {
                    step: 1,
                    style: {
                        fontSize: 13,
                        color: '#222'
                    }
                },
                accessibility: {
                    description: 'Age (female)'
                }
            }],

            plotOptions: {
                series: {
                    stacking: 'normal',
                    groupPadding: 0,
                    maxPointWidth: 55
                }
            },

            tooltip: {
                formatter: function () {
                    return '<b>' + this.series.name + ', age ' + this.point.category + '</b><br/>' +
                        'Population: ' + Highcharts.numberFormat(Math.abs(this.point.y), 1) + '%';
                }
            },

            $legend

            series: [{
                animation: {
                    defer: 300,
                    duration: 200
                },
                name: 'Hommes',
                color: '$barsColorM',
                data: [$hommes]
            }, {
                animation: {
                    defer: 300,
                    duration: 200
                },
                name: 'Femmes',
                color: '$barsColorF',
                data: [$femmes]
            }]
        });
        eof;
    }


    private function regTitle()
    {
        // Pays
        $this->title .= ' | ' . tools::getCountries()[$_SESSION['eurostat_filterCountry']];

        // Sexe
        if ($_SESSION['eurostat_filterSex'] != 'T') {
            $this->title .= ' | ' . tools::getSex()[$_SESSION['eurostat_filterSex']];
        }

        // Ages
        if ($_SESSION['eurostat_filterAge'] != 'TOTAL') {
            $this->title .= ' | ' . tools::rangeFilterAge()[$_SESSION['eurostat_filterAge']];
        }
    }


    /**
     * Redu HTML
     */
    public function render()
    {
        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [
            'charts'    => [true,  'col-lg-5'],
            'countries' => [true,  'col-lg-3'],
            'year1'     => [true,  'col-lg-2'],
            'unit'      => [true,  'col-lg-2'],
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
