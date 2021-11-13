<?php

namespace spf\charts;

use tools\dbSingleton;
use main\HighChartsCommon;
use main\Cache;
use DateTime;
use DateInterval;

class NbCumuleDeces
{
    private $cache;
    private $dbh;

    private $chartName;

    private $title;
    private $subTitle;

    private $yAxisLabel;

    private $data;
    private $highChartsJs;

    private $years;

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

        $this->chartName = 'NbCumuleDeces';

        $this->title    = 'Nb cumulé de décès covid-19 | Taux de positivité covid-19';
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours) | Données hospitalières';

        $this->yAxisLabel = 'Nb cumulé de décès covid-19';

        $this->getYears();
        $this->getData();
        $this->highChartsJs();
    }


    /**
     * Récupération de la liste des années à traiter
     */
    public function getYears()
    {
        $this->years = [];
        
        $req  = "SELECT DISTINCT(DATE_FORMAT(jour, '%Y')) AS years FROM donnees_hp_cumule_age_covid19_reg_calc";
        $sql  = $this->dbh->query($req);

        $req2 = "SELECT jour FROM donnees_hp_cumule_age_covid19_reg_calc WHERE jour LIKE :year LIMIT 1";
        $sql2  = $this->dbh->prepare($req2);

        while ($res = $sql->fetch()) {
            $sql2->execute([':year' => $res->years . '%']);
            $res2 = $sql2->fetch();
            $jour = $res2->jour;

            $lastYear   = ($res->years - 1) . '-' . $res->years;
            $actualYear = $res->years . '-' . ($res->years + 1);

            if ($jour < $res->years . '-07-01' && !in_array($lastYear, $this->years)) {
                $this->years[] = $lastYear;
            }

            if (!in_array($actualYear, $this->years)) {
                $this->years[] = $actualYear;
            }
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

        $addReq .= " AND cl_age90 = :cl_age90";
        if (!empty($_SESSION['spf_filterAge']) && $_SESSION['spf_filterAge'] != '0') {
            $addReqValues[':cl_age90'] = $_SESSION['spf_filterAge'];
            $fileName .= '_age_' . $_SESSION['spf_filterAge'];
        } else {
            $addReqValues[':cl_age90'] = 0;
        }

        if ($this->cache && $this->data = Cache::getCache($fileName)) {
            return;
        }

        $this->data = [];
        $cumul = [];

        foreach ($this->years as $year) {
            $exp = explode('-', $year);

            $dateDeb = $exp[0] . '-07-01';
            $dateFin = $exp[1] . '-06-30';

            $addReqValues2 = array_merge(
                [
                    ':dateDeb' => $dateDeb,
                    ':dateFin' => $dateFin,
                ],
                $addReqValues
            );

            $yearDays = $this->listDays($dateDeb, $dateFin);
            
            foreach ($yearDays as $day) {
                $this->data[$year][$day]['sum_dc'] = "null";
            }

            $req = "SELECT      jour,
                                SUM(dc) AS sum_dc
    
                    FROM        donnees_hp_cumule_age_covid19_reg_calc
    
                    WHERE       1 $addReq
                    AND         jour >= :dateDeb
                    AND         jour <= :dateFin
    
                    GROUP BY    jour
                    ORDER BY    jour ASC";
            
            $sql = $this->dbh->prepare($req);
            $sql->execute($addReqValues2);
    
            while ($res = $sql->fetch()) {
                $jour = $res->jour;
  
                $this->data[$year][$jour]['sum_dc'] = empty($res->sum_dc) ? 'null' : $res->sum_dc;
                
                if (is_null($this->data[$year][$jour]['sum_dc'])) {
                    continue;
                }

                $lastYear = ($exp[0] - 1) . '-' . $exp[0];

                if (isset($this->data[$lastYear][$exp[0] . '-06-30']['sum_dc']) && $this->data[$lastYear][$exp[0] . '-06-30']['sum_dc'] != 'null') {
                    $cumul[$lastYear] =  $this->data[$lastYear][$exp[0] . '-06-30']['sum_dc'];
                    $this->data[$year][$jour]['sum_dc'] -= array_sum($cumul);
                }
            }
        }

        // createCache
        if ($this->cache) {
            Cache::createCache($fileName, $this->data);
        }
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $yearStart = substr($this->years[0], 0, 4);
        $yearsSeries = [];

        $colors = [
            '#f89200',
            '#106097',
            '#c70000',
            '#236b53',
            '#bb45a0',
        ];
        
        $i = 0;
        foreach ($this->data as $years => $jours) {
            $listJoursDc = [];

            foreach ($jours as $jour => $sum_dc) {
                $sum_dc = $sum_dc['sum_dc'];
                if ($sum_dc != 'null') {
                    $sum_dc = round($sum_dc);
                }

                $listJoursDc[] = "['" . str_replace('-', '/', $jour) . "', " . $sum_dc . "]";
            }

            $listJoursDc = implode(', ', $listJoursDc);

            $color = $colors[$i];

            $yearsSeries[] = <<<eof
                {
                    connectNulls: true,
                    marker:{
                        enabled:false
                    },
                    name: '{$this->yAxisLabel} : $years',
                    color: '$color',
                    yAxis: 0,
                    data: [$listJoursDc]
                }
eof;
        
            $i++;
        }

        $series  = 'series: [';
        $series .= implode(',', $yearsSeries);
        $series .= '],';

        $credit     = HighChartsCommon::creditLCH();
        $event      = HighChartsCommon::exportImgLogo();
        $legend     = HighChartsCommon::legend();
        $responsive = HighChartsCommon::responsive();

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

            yAxis: [{
                title: {
                    text: '{$this->yAxisLabel}',
                    style: {
                        color: '#106097',
                        fontSize: 16
                    }
                },
                labels: {
                    format: '{value}',
                    style: {
                        color: '#106097',
                        fontSize: 16
                    },
                    formatter: function() {
                        return Highcharts.numberFormat(this.value, 0, '.', ' ');
                    }
                },
                opposite: true
            }],

            xAxis: {
                type: 'datetime',
                tickInterval: 24 * 3600 * 1000 * 30,
                dateTimeLabelFormats: {
                    day: "%e. %b",
                    month: "%b",
                    year: "%Y"
                },
                formatter: function() {
                    var x = new Date(this.value);
                    return x.getFullMonth() + '<br /> Month: ' + x.getDay();
                },

                gridLineWidth: 1,
                labels: {
                    rotation: 0,
                    style: {
                        fontSize: 16
                    }
                },
                tickWidth: 1,
                tickLength: 7,
            },

            $legend

            $series

            plotOptions: {
                series: {
                    pointStart: Date.UTC($yearStart, 6, 1),
                    pointInterval: 24 * 3600 * 1000             // One day
                }
            },

            $responsive
        });
        eof;
    }


    private function regTitle()
    {
        $this->title .= ($_SESSION['spf_filterRegionId'] == 0)
                            ? ' | ' . $_SESSION['spf_filterRegionName']
                            : ' | Région : ' . HighChartsCommon::chartText($_SESSION['spf_filterRegionName']);
    }


    /**
     * Redu HTML
     */
    public function render()
    {
        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [
            'charts'    => [true, 'col-lg-4'],
            'region'    => [true, 'col-lg-4'],
            // 'interval'  => [true, 'col-lg-2'],
            'age'       => [true, 'col-lg-2'],
        ];

        echo Render::html(
            $this->chartName,
            $this->title,
            $this->highChartsJs,
            $backLink,
            $filterActiv
        );
    }


    /**
     * Retourne toutes les jours entre 2 dates (incluses)
     */
    public function listDays($dateDeb, $dateFin)
    {
        $listDays = [$dateDeb];
        
        $d = new DateTime($dateDeb);

        $i = 0;
        while ($i < 10000) {
            $d->add(new DateInterval('P1D'));
            $listDays[] = $d->format('Y-m-d');
            
            if ($d->format('Y-m-d') == $dateFin) {
                return $listDays;
            }

            $i++;
        }
    }
}
