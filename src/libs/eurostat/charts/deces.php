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

        $this->getData();
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

        // if (!empty($_SESSION['eurostat_filterYear1'])) {
        //     $addReq .= " AND year = :year";
        //     $addReqValues[':year'] = $_SESSION['eurostat_filterYear1'];
        //     $fileName .= '_year1_' . $_SESSION['eurostat_filterYear1'];
        // }

        if (!empty($_SESSION['eurostat_filterSex'])) {
            $addReq .= " AND sex = :sex";
            $addReqValues[':sex'] = $_SESSION['eurostat_filterSex'];
            $fileName .= '_sex_' . $_SESSION['eurostat_filterSex'];
        }

        if (!empty($_SESSION['eurostat_filterAge'])) {
            $addReq .= " AND age = :age";
            $addReqValues[':age'] = $_SESSION['eurostat_filterAge'];
            $fileName .= '_age_' . $_SESSION['eurostat_filterAge'];
        }

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        $req = "SELECT      year, value
                FROM        eurostat_demo_magec
                WHERE       value IS NOT NULL
                $addReq
                ORDER BY    year ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        $req2 = str_replace(':geotime', "'". $_SESSION['eurostat_filterCountry'] ."'",  $req);
        $req2 = str_replace(':sex', "'". $_SESSION['eurostat_filterSex'] ."'",  $req2);
        $req2 = str_replace(':age', "'". $_SESSION['eurostat_filterAge'] ."'",  $req2);

        echo $req2;
        echo '<hr>';

        while ($res = $sql->fetch()) {
            $this->data[$res->year] = $res->value;
        }

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
            'countries' => [true,  'col-lg-3'],
            'sex'       => [true,  'col-lg-3'],
            'age'       => [true,  'col-lg-3'],
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
