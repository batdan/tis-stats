<?php
namespace eurostat\charts;

use tools\dbSingleton;
use tools\config;

use main\highChartsCommon;
use eurostat\main\tools;

class decesStandardises
{
    private $standardYear;      // Année standard
    private $popStandard;       // Population standardisée

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

        $this->chartName = 'decesStandardises';

        $this->title    = 'Décès standardisés toutes causes confondues';
        $this->regTitle();

        $maj = tools::lastMajData();
        $this->subTitle  = 'Selon la population de la France en 2020 | ';
        $this->subTitle .= (!empty($maj)) ? 'Source: Eurostats | ' . $maj : 'Source: Eurostats';

        $this->yAxisLabel = 'Nb cumulé de décès';

        $this->sandardYear();
        $this->getData();
        $this->highChartsJs();
    }


    /**
     * Calcul et mise en cache de la population standardisée
     * Choix d'une année en France
     */
    public function sandardYear()
    {
        // Année standardisé
        $eurostat = config::getConfig('eurostat');
        $this->standardYear = $eurostat['standardYear'];

        // Gestion des caches
        $className = str_replace('\\', '_', get_class($this));
        $fileName  = date('Y-m-d_') . $className . '_standardYear_' . $this->standardYear;

        if ($this->cache && $this->popStandard = \main\cache::getCache($fileName)) {
            return;
        }

        $this->popStandard = [];

        $keysFilterAge = array_keys(tools::rangeFilterAge());

        foreach ($keysFilterAge as $key) {

            $addReq = tools::magecFilterAge($key);

            $req = "SELECT  SUM(value) AS sumValue
                    FROM    eurostat_demo_pjan
                    WHERE   year    = :year
                    AND     geotime = :geotime
                    AND     sex     = :sex
                    $addReq";

            $sql = $this->dbh->prepare($req);
            $sql->execute([
                ':year'     => $this->standardYear,
                ':sex'      => 'T',
                ':geotime'  => 'FR',
            ]);

            if ($sql->rowCount()) {
                $res = $sql->fetch();
                $this->popStandard[$key] = $res->sumValue;
            }
        }

        // createCache
        if ($this->cache) {
            \main\cache::createCache($fileName, $this->popStandard);
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
        if (!empty($_SESSION['eurostat_filterCountry'])) {
            $addReq .= " AND geotime = :geotime";
            $addReqValues[':geotime'] = $_SESSION['eurostat_filterCountry'];
            $fileName .= '_country_' . $_SESSION['eurostat_filterCountry'];
        }

        if (!empty($_SESSION['eurostat_filterSex'])) {
            $addReq .= " AND sex = :sex";
            $addReqValues[':sex'] = $_SESSION['eurostat_filterSex'];
            $fileName .= '_sex_' . $_SESSION['eurostat_filterSex'];
        }

        if (!empty($_SESSION['eurostat_filterAge'])) {
            $addReq .= tools::magecFilterAge($_SESSION['eurostat_filterAge']);
            $fileName .= '_age_' . $_SESSION['eurostat_filterAge'];
        }

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
            return;
        }

        $this->data = [];
        $dataDeces  = [];
        $dataPop    = [];

        // Récupération des décès
        $req = "SELECT      year, SUM(value) AS sumValue
                FROM        eurostat_demo_magec
                WHERE       value IS NOT NULL
                $addReq
                GROUP BY    year
                ORDER BY    year ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $dataDeces[$res->year] = $res->sumValue;
        }

        // Récupération de la population / année
        $years = array_keys($dataDeces);
        $addYears = implode(',', $years);

        $req = "SELECT      year, value
                FROM        eurostat_demo_pjan
                WHERE       year IN ($addYears)
                $addReq
                ORDER BY    year ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $dataPop[$res->year] = $res->value;
        }

        foreach ($dataDeces as $year => $value) {
            $this->data[$year] = round($value / $dataPop[$year] * $this->popStandard[$_SESSION['eurostat_filterAge']]);
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
        $years = [];
        $value = [];

        foreach($this->data as $year => $val) {
            $years[] = "'" . $year . "'";
            $value[] = $val;
        }

        $years = implode(', ', $years);
        $value = implode(', ', $value);

        $credit     = highChartsCommon::creditLCH();
        $event      = highChartsCommon::exportImgLogo();
        $xAxis      = highChartsCommon::xAxisYears($years);
        $legend     = highChartsCommon::legend();
        $responsive = highChartsCommon::responsive();

        $barsColor  = tools::getSexColor()[$_SESSION['eurostat_filterSex']];

        $this->highChartsJs = <<<eof
        Highcharts.chart('{$this->chartName}', {

            $credit

            $event

            chart: {
                type: 'column',
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
                name: '{$this->yAxisLabel}',
                color: '$barsColor',
                yAxis: 0,
                data: [$value]
            }],

            $responsive
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
            'charts'    => [true,  'col-lg-3'],
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
