<?php

namespace spf\charts;

use tools\dbSingleton;
use main\HighChartsCommon;
use main\Cache;

/**
 * Nombre quotidien de vaccinés par vaccin
 */
class NbCumuleVaccinationVaccin
{
    private $cache;
    private $dbh;

    private $chartName;

    private $title;
    private $subTitle;

    private $yAxis1Label;
    private $yAxis2Label;
    private $yAxis3Label;
    private $yAxis4Label;

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

        $this->chartName = 'NbCumuleVaccinationVaccin';

        $this->title    = 'Pourcentage de vaccinés covid-19 par vaccin';
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France (lissé sur 7 jours) | Vaccination';

        $this->yAxis1Label = 'Pourcentage de vaccinés 1ère dose';
        $this->yAxis2Label = 'Pourcentage de vaccinés 2ème dose';
        $this->yAxis3Label = 'Pourcentage de vaccinés 3ème dose';
        $this->yAxis4Label = 'Pourcentage de vaccinés 4ème dose';

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

        if (!empty($_SESSION['spf_filterUnite'])) {
            $fileName .= '_' . $_SESSION['spf_filterUnite'];
        }

        if ($this->cache && $this->data = Cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        $req = "SELECT      jour,
                            SUM(n_cum_dose1) AS sum_n_cum_dose1,
                            SUM(n_cum_dose2) AS sum_n_cum_dose2,
                            SUM(n_cum_dose3) AS sum_n_cum_dose3,
                            SUM(n_cum_dose4) AS sum_n_cum_dose4

                FROM        donnees_vaccination_vaccin_covid19

                WHERE       1 $addReq

                GROUP BY    jour
                ORDER BY    jour ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->jour] = [
                'sum_n_cum_dose1' => $res->sum_n_cum_dose1,
                'sum_n_cum_dose2' => $res->sum_n_cum_dose2,
                'sum_n_cum_dose3' => $res->sum_n_cum_dose3,
                'sum_n_cum_dose4' => $res->sum_n_cum_dose4,
            ];
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
        $jours       = [];
        $n_cum_dose1 = [];
        $n_cum_dose2 = [];
        $n_cum_dose3 = [];
        $n_cum_dose4 = [];

        foreach ($this->data as $jour => $res) {
            $jours[] = "'" . $jour . "'";

            if ($_SESSION['spf_filterUnite'] == 'quantity') {
                $n_cum_dose1[] = $res['sum_n_cum_dose1'];
                $n_cum_dose2[] = $res['sum_n_cum_dose2'];
                $n_cum_dose3[] = $res['sum_n_cum_dose3'];
                $n_cum_dose4[] = $res['sum_n_cum_dose4'];
            } else {
                $n_cum_dose1[] = 100 / $this->regions[$_SESSION['spf_filterRegionId']] * $res['sum_n_cum_dose1'];
                $n_cum_dose2[] = 100 / $this->regions[$_SESSION['spf_filterRegionId']] * $res['sum_n_cum_dose2'];
                $n_cum_dose3[] = 100 / $this->regions[$_SESSION['spf_filterRegionId']] * $res['sum_n_cum_dose3'];
                $n_cum_dose4[] = 100 / $this->regions[$_SESSION['spf_filterRegionId']] * $res['sum_n_cum_dose4'];
            }
        }

        $jours       = implode(', ', $jours);
        $n_cum_dose1 = implode(', ', $n_cum_dose1);
        $n_cum_dose2 = implode(', ', $n_cum_dose2);
        $n_cum_dose3 = implode(', ', $n_cum_dose3);
        $n_cum_dose4 = implode(', ', $n_cum_dose4);

        $credit     = HighChartsCommon::creditLCH();
        $event      = HighChartsCommon::exportImgLogo();
        $xAxis      = HighChartsCommon::xAxis($jours);
        $legend     = HighChartsCommon::legend();
        $responsive = HighChartsCommon::responsive();

        switch ($_SESSION['spf_filterUnite']) {
            case 'quantity':
                $tooltip    = '';
                $format     = '{value}';
                $formatter  = "formatter: function() {return Highcharts.numberFormat(this.value, 0, '.', ' ');},";
                break;
            case 'percent':
                $tooltip    = "tooltip: {valueDecimals:2, valueSuffix:'%'},";
                $format     = '{value:.2f}%';
                $formatter  = '';
                break;
        }

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
                    text: 'Pourcentage de vaccinés',
                    style: {
                        color: '#c70000',
                        fontSize: 14
                    }
                },
                labels: {
                    format: '$format',
                    allowDecimals: 2,
                    $formatter
                    style: {
                        color: '#c70000',
                        fontSize: 14
                    }
                },
                opposite: true
            }],

            $xAxis

            $legend

            $tooltip

            series: [{
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis1Label}',
                color: '#4aaf42',
                yAxis: 0,
                data: [$n_cum_dose1]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis2Label}',
                color: '#106097',
                yAxis: 0,
                data: [$n_cum_dose2]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis3Label}',
                color: '#c70000',
                yAxis: 0,
                data: [$n_cum_dose3]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis4Label}',
                color: '#9032ff',
                yAxis: 0,
                data: [$n_cum_dose4]
            }],

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
            'charts'    => [true, 'col-lg-3'],
            'region'    => [true, 'col-lg-3'],
            'interval'  => [true, 'col-lg-2'],
            'vaccin'    => [true, 'col-lg-2'],
            'unite'     => [true, 'col-lg-2'],
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
