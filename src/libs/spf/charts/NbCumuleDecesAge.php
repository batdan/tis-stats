<?php

namespace spf\charts;

use tools\dbSingleton;
use main\HighChartsCommon;
use main\Cache;
use DateTime;

class NbCumuleDecesAge
{
    private $cache;
    private $dbh;

    private $chartName;
    private $measures;

    private $title;
    private $subTitle;

    private $data;
    private $highChartsJs;

    private $ages;


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

        $this->chartName = 'NbCumuleDecesAge';

        $this->title    = 'Nb cumulé de décès par âge covid-19';
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours) | Données hospitalières';

        $this->ages = [
            '09' => 'De 0 à 9 ans',
            '19' => 'De 10 à 19 ans',
            '29' => 'De 20 à 29 ans',
            '39' => 'De 30 à 39 ans',
            '49' => 'De 40 à 49 ans',
            '59' => 'De 50 à 59 ans',
            '69' => 'De 60 à 69 ans',
            '79' => 'De 70 à 79 ans',
            '89' => 'De 80 à 89 ans',
            '90' => '90 ans et plus',
        ];

        $this->getData();
        $this->getMeasures();
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

        if ($this->cache && $this->data = Cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        foreach ($this->ages as $k => $v) {
            $addReqValues[':cl_age90'] = $k;

            $req = "SELECT      jour,
                                SUM(dc) AS sum_dc

                    FROM        donnees_hp_cumule_age_covid19_reg_calc_lisse7j

                    WHERE       1 $addReq
                    AND         cl_age90 = :cl_age90

                    GROUP BY    jour
                    ORDER BY    jour ASC";

            $sql = $this->dbh->prepare($req);
            $sql->execute($addReqValues);

            while ($res = $sql->fetch()) {
                $this->data[$res->jour][$k] = (!isset($res->sum_dc)) ? null : $res->sum_dc;
            }
        }

        // createCache
        if ($this->cache) {
            Cache::createCache($fileName, $this->data);
        }
    }


    /**
     * Récupération des confinements
     */
    private function getMeasures()
    {
        $this->measures = [];

        $req = "SELECT  date_start, date_end 
                FROM    ecdc_response_measure 
                WHERE   iso_3166_1_alpha_2  = :iso_3166_1_alpha_2 
                AND     response_measure    = :response_measure";

        $sql = $this->dbh->prepare($req);
        $sql->execute([
            ':iso_3166_1_alpha_2'   => 'FR',
            ':response_measure'     => 'StayHomeOrder',
        ]);

        while ($res = $sql->fetch()) {
            $this->measures[] = [
                'date_start'    => $res->date_start,
                'date_end'      => $res->date_end,
            ];
        }
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $jours = [];
        $dc    = [];

        foreach ($this->data as $jour => $res) {
            if (!isset($dateDeb_Y)) {
                $expDateDeb = explode('-', $jour);
                $dateDeb_Y  = $expDateDeb[0];
                $dateDeb_m  = intval($expDateDeb[1]) - 1;
                $dateDeb_d  = intval($expDateDeb[2]);
            }

            $jours[] = "'" . $jour . "'";

            foreach ($this->ages as $k => $v) {
                $dc[$k][] = round($res[$k], 2);
            }
        }

        $jours = implode(', ', $jours);

        $series = [];

        foreach ($this->ages as $k => $v) {
            $dcSeries = implode(', ', $dc[$k]);

            $series[] = <<<eof
            {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '$v',
                data: [$dcSeries]
            }
eof;
        }

        $series = implode(',', $series);
        $series = 'series: [' . $series . '],' . chr(10);

        $plotDand = [];
        foreach ($this->measures as $measure) {
            $d = new DateTime($measure['date_start']);
            $date_start_Y = $d->format('Y');
            $date_start_m = intval($d->format('m')) - 1;
            $date_start_d = intval($d->format('d'));

            $d = new DateTime($measure['date_end']);
            $date_end_Y = $d->format('Y');
            $date_end_m = intval($d->format('m')) - 1;
            $date_end_d = intval($d->format('d'));

            $plotDand[] = <<<eof
{
            color: '#fbe4c2',
            from: Date.UTC($date_start_Y, $date_start_m, $date_start_d),
            to: Date.UTC($date_end_Y, $date_end_m, $date_end_d),
            label: { 
                text: 'Confinement', 
                rotation: -90,
                align: 'left', 
                x: -5,
                y: 80
            }
        }
eof;
        }

        $plotDand  = implode(',', $plotDand);
        $plotDands = "plotBands: [$plotDand]";

        $credit     = HighChartsCommon::creditLCH();
        $event      = HighChartsCommon::exportImgLogo();
        $xAxis      = HighChartsCommon::xAxis([], false, $plotDands);
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
                    text: 'Nombre de personnes',
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

            $xAxis

            $legend

            $series

            plotOptions: {
                series: {
                    pointStart: Date.UTC($dateDeb_Y, $dateDeb_m, $dateDeb_d),
                    pointInterval: 24 * 3600 * 1000     // One day
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
            'interval'  => [true, 'col-lg-2'],
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
}
