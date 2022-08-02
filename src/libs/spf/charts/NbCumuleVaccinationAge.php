<?php

namespace spf\charts;

use tools\dbSingleton;
use main\HighChartsCommon;
use main\Cache;

/**
 * Nombre quotidien de vaccinés par age
 */
class NbCumuleVaccinationAge
{
    private $cache;
    private $dbh;

    private $chartName;

    private $title;
    private $subTitle;

    private $curve1;
    private $curve2;
    private $curve3;
    private $curve4;
    private $yAxisLabel;

    private $regions;
    private $data;
    private $highChartsJs;

    private $warning;


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

        $this->chartName = 'NbCumuleVaccinationAge';

        $this->title    = 'Pourcentage de vaccinés covid-19 par âge';
        $this->regTitle();

        $this->subTitle = 'Source: Santé Publique France | Vaccination';

        $unite = ($_SESSION['spf_filterUnite'] == 'quantity') ? 'Nombre' : 'Pourcentage';

        $this->curve1   = $unite . ' de vaccinés 1ère dose';
        $this->curve2   = $unite . ' de vaccinés 2ème dose';
        $this->curve3   = $unite . ' de vaccinés 3ème dose';
        $this->curve4   = $unite . ' de vaccinés 4ème dose';

        $this->yAxisLabel = $unite . ' de vaccinés';

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

        if ($_SESSION['spf_filterRegionId'] == '0' && $_SESSION['spf_filterUnite'] == 'percent' && $_SESSION['spf_filterAge2'] != '0') {
            switch ($_SESSION['spf_filterAge2']) {
                case '04':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age <= 4";
                    break;
                case '09':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 5  AND age <= 9";
                    break;
                case '11':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 10 AND age <= 11";
                    break;
                case '17':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 12 AND age <= 17";
                    break;
                case '24':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 18 AND age <= 24";
                    break;
                case '29':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 25 AND age <= 29";
                    break;
                case '39':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 30 AND age <= 39";
                    break;
                case '49':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 40 AND age <= 49";
                    break;
                case '59':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 50 AND age <= 59";
                    break;
                case '64':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 60 AND age <= 64";
                    break;
                case '69':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 65 AND age <= 69";
                    break;
                case '74':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 70 AND age <= 74";
                    break;
                case '79':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 75 AND age <= 79";
                    break;
                case '80':
                    $req = "SELECT SUM(total) AS sum_total FROM insee_pyramide_age_france_2021 WHERE age >= 80";
                    break;
            }

            $sql = $this->dbh->query($req);
            $res = $sql->fetch();

            $this->regions[0] = $res->sum_total;
        }

        if ($_SESSION['spf_filterRegionId'] != '0' && $_SESSION['spf_filterUnite'] == 'percent' && $_SESSION['spf_filterAge2'] != '0') {
            $this->warning  = 'Attention, si vous sélectionnez <u><b>une région</b></u>, <u><b>un âge</b></u> et un résultat en <u><b>pourcentage</b></u>, ';
            $this->warning .= 'le calcul sera fait sur la population de toute la France';
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

        $addReq .= " AND clage_vacsi = :clage_vacsi";
        if (!empty($_SESSION['spf_filterAge2']) && $_SESSION['spf_filterAge2'] != '0') {
            $addReqValues[':clage_vacsi'] = $_SESSION['spf_filterAge2'];
            $fileName .= '_age_' . $_SESSION['spf_filterAge2'];
        } else {
            $addReqValues[':clage_vacsi'] = 0;
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

                FROM        donnees_vaccination_age_covid19

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
            $jours[]       = "'" . $jour . "'";
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

            yAxis: [{ // Primary yAxis
                title: {
                    text: '{$this->yAxisLabel}',
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
                name: '{$this->curve1}',
                color: '#4aaf42',
                yAxis: 0,
                data: [$n_cum_dose1]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->curve2}',
                color: '#106097',
                yAxis: 0,
                data: [$n_cum_dose2]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->curve3}',
                color: '#c70000',
                yAxis: 0,
                data: [$n_cum_dose3]
            }, {
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->curve4}',
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
            'age2'      => [true, 'col-lg-2'],
            'unite'     => [true, 'col-lg-2'],
        ];

        echo Render::html(
            $this->chartName,
            $this->title,
            $this->highChartsJs,
            $backLink,
            $filterActiv,
            $this->warning
        );
    }
}
