<?php

namespace spf\charts;

use tools\dbSingleton;
use main\HighChartsCommon;
use main\Cache;
use DateTime;

/**
 * Nombre quotidien de vaccinés par vaccin
 */
class QuotidienVaccinationVaccin
{
    private $cache;
    private $dbh;

    private $chartName;
    private $measures;

    private $title;
    private $subTitle;

    private $yAxis1Label;
    private $yAxis2Label;
    private $yAxis3Label;
    private $yAxis4Label;

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

        $this->chartName = 'QuotidienVaccinationAge';

        $this->title    = 'Nombre quotidien de vaccinés covid-19 par vaccin';
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours) | Vaccination';

        $this->yAxis1Label = 'Nb quotidien de vaccinés 1ère dose';
        $this->yAxis2Label = 'Nb quotidien de vaccinés 2ème dose';
        $this->yAxis3Label = 'Nb quotidien de vaccinés 3ème dose';
        $this->yAxis4Label = 'Nb quotidien de vaccinés 4ème dose';

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

        $addReq .= " AND vaccin = :vaccin";
        if (!empty($_SESSION['spf_filterVaccin']) && $_SESSION['spf_filterVaccin'] != '0') {
            $addReqValues[':vaccin'] = $_SESSION['spf_filterVaccin'];
            $fileName .= '_vaccin_' . $_SESSION['spf_filterVaccin'];
        } else {
            $addReqValues[':vaccin'] = 0;
        }

        if ($this->cache && $this->data = Cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        $req = "SELECT      jour,
                            SUM(n_dose1) AS sum_n_dose1,
                            SUM(n_dose2) AS sum_n_dose2,
                            SUM(n_dose3) AS sum_n_dose3,
                            SUM(n_dose4) AS sum_n_dose4

                FROM        donnees_vaccination_vaccin_covid19_calc_lisse7j

                WHERE       1 $addReq

                GROUP BY    jour
                ORDER BY    jour ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->jour] = [
                'sum_n_dose1' => $res->sum_n_dose1,
                'sum_n_dose2' => $res->sum_n_dose2,
                'sum_n_dose3' => $res->sum_n_dose3,
                'sum_n_dose4' => $res->sum_n_dose4,
            ];
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
        $jours      = [];
        $n_dose1    = [];
        $n_dose2    = [];
        $n_dose3    = [];
        $n_dose4    = [];

        foreach ($this->data as $jour => $res) {
            if (!isset($dateDeb_Y)) {
                $expDateDeb = explode('-', $jour);
                $dateDeb_Y  = $expDateDeb[0];
                $dateDeb_m  = intval($expDateDeb[1]) - 1;
                $dateDeb_d  = intval($expDateDeb[2]);
            }

            $jours[]    = "'" . $jour . "'";
            $n_dose1[]  = $res['sum_n_dose1'];
            $n_dose2[]  = $res['sum_n_dose2'];
            $n_dose3[]  = $res['sum_n_dose3'];
            $n_dose4[]  = $res['sum_n_dose4'];
        }

        $jours      = implode(', ', $jours);
        $n_dose1    = implode(', ', $n_dose1);
        $n_dose2    = implode(', ', $n_dose2);
        $n_dose3    = implode(', ', $n_dose3);
        $n_dose4    = implode(', ', $n_dose4);

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

            yAxis: [{ // Primary yAxis
                title: {
                    text: 'Nombre quotidien de vaccinés',
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

            $xAxis

            $legend

            series: [{
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis1Label}',
                color: '#4aaf42',
                yAxis: 0,
                data: [$n_dose1]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis2Label}',
                color: '#106097',
                yAxis: 0,
                data: [$n_dose2]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis3Label}',
                color: '#c70000',
                yAxis: 0,
                data: [$n_dose3]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis4Label}',
                color: '#9032ff',
                yAxis: 0,
                data: [$n_dose4]
            }],

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
            'vaccin'    => [true, 'col-lg-2'],
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
