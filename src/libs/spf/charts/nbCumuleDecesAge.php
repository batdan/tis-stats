<?php
namespace spf\charts;

use tools\dbSingleton;
use main\highChartsCommon;

class nbCumuleDecesAge
{
    private $cache;
    private $dbh;

    private $chartName;

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

        $this->chartName = 'nbCumuleDecesAge';

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

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
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
        \main\cache::createCache($fileName, $this->data);
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $jours = [];
        $dc    = [];

        foreach($this->data as $jour => $res) {
            $jours[] = "'".$jour."'";

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

        $credit     = highChartsCommon::creditLCH();
        $event      = highChartsCommon::exportImgLogo();
        $xAxis      = highChartsCommon::xAxis($jours);
        $legend     = highChartsCommon::legend();
        $responsive = highChartsCommon::responsive();

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

            $responsive
        });
        eof;
    }


    private function regTitle()
    {
        $this->title .= ($_SESSION['spf_filterRegionId'] == 0)
                            ? ' | ' . $_SESSION['spf_filterRegionName']
                            : ' | Région : ' . highChartsCommon::chartText($_SESSION['spf_filterRegionName']);
    }


    /**
     * Redu HTML
     */
    public function render()
    {
        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [
            'region'    => true,
            'interval'  => true,
            'age'       => false,
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
