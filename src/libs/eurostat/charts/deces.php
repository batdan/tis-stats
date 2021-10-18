<?php
namespace eurostat\charts;

use tools\dbSingleton;
use main\highChartsCommon;

class deces
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

        $this->chartName = 'deces';

        $this->title    = 'Nb cumulé de décès toutes causes confondues';
        // $this->regTitle();

        $this->subTitle = 'Source: Eurostats';

        $this->yAxisLabel = 'Nb cumulé de décès';

        // $this->getData();
        // $this->highChartsJs();
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
            $fileName .= '_year1_' . $_SESSION['eurostat_filterYear1'];
        }

        if (!empty($_SESSION['eurostat_filterSex'])) {
            $addReq .= " AND year = :year";
            $addReqValues[':sex'] = $_SESSION['eurostat_filterSex'];
            $fileName .= '_sex_' . $_SESSION['eurostat_filterSex'];
        }

        $addReq .= " AND age = :age";
        if (!empty($_SESSION['eurostat_filterAge'])) {
            $addReqValues[':age'] = $_SESSION['eurostat_filterAge'];
            $fileName .= '_age_' . $_SESSION['eurostat_filterAge'];
        } else {
            $addReqValues[':cl_age90'] = 0;
        }

        // if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
        //     return;
        // }
        // 
        // $this->data = [];
        // 
        // $req = "SELECT      jour,
        //                     SUM(dc) AS sum_dc
        // 
        //         FROM        donnees_hp_cumule_age_covid19_reg_calc_lisse7j
        // 
        //         WHERE       1 $addReq
        // 
        //         GROUP BY    jour
        //         ORDER BY    jour ASC";
        // 
        // $sql = $this->dbh->prepare($req);
        // $sql->execute($addReqValues);
        // 
        // while ($res = $sql->fetch()) {
        //     $this->data[$res->jour]['sum_dc'] = (!isset($res->sum_dc)) ? null : $res->sum_dc;
        // }
        // 
        // // createCache
        // \main\cache::createCache($fileName, $this->data);
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $jours  = [];
        $dc     = [];

        foreach($this->data as $jour => $res) {
            $jours[] = "'".$jour."'";
            $dc[]    = round($res['sum_dc'], 2);
        }

        $jours  = implode(', ', $jours);
        $dc     = implode(', ', $dc);

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

            $xAxis

            $legend

            series: [{
                connectNulls: true,
                marker:{
                    enabled:false
                },
                name: '{$this->yAxis1Label}',
                color: '#106097',
                yAxis: 0,
                data: [$dc]
            }],

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
            'countries' => true,
            'year1'     => true,
            'sex'       => true,
            'age'       => true,
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
