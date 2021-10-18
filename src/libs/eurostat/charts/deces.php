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

        if (!empty($_SESSION['eurostat_filterSex'])) {
            $addReq .= " AND sex = :sex";
            $addReqValues[':sex'] = $_SESSION['eurostat_filterSex'];
            $fileName .= '_sex_' . $_SESSION['eurostat_filterSex'];
        }

        if (!empty($_SESSION['eurostat_filterAge'])) {
            $addReq .= $this->filterAge($_SESSION['eurostat_filterAge']);
            $fileName .= '_age_' . $_SESSION['eurostat_filterAge'];
        }

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
            return;
        }

        $this->data = [];

        $req = "SELECT      year, SUM(value) AS sumValue
                FROM        eurostat_demo_magec
                WHERE       value IS NOT NULL
                $addReq
                GROUP BY    year
                ORDER BY    year ASC";


        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);

        while ($res = $sql->fetch()) {
            $this->data[$res->year] = $res->sumValue;
        }

        // createCache
        if ($this->cache) {
            \main\cache::createCache($fileName, $this->data);
        }
    }


    private function filterAge($range)
    {
        switch ($range)
        {
            case 'TOTAL'  : return " AND age = 'TOTAL'";
            case 'Y_LT5'  : return " AND (age='Y_LT1' OR age='Y1'  OR age='Y2'  OR age='Y3'  OR age='Y4')";
            case 'Y5-9'   : return " AND (age='Y5'    OR age='Y6'  OR age='Y7'  OR age='Y8'  OR age='Y9')";
            case 'Y10-14' : return " AND (age='Y10'   OR age='Y11' OR age='Y12' OR age='Y13' OR age='Y14')";
            case 'Y15-19' : return " AND (age='Y15'   OR age='Y16' OR age='Y17' OR age='Y18' OR age='Y19')";
            case 'Y20-24' : return " AND (age='Y20'   OR age='Y21' OR age='Y22' OR age='Y23' OR age='Y24')";
            case 'Y25-29' : return " AND (age='Y25'   OR age='Y26' OR age='Y27' OR age='Y28' OR age='Y29')";
            case 'Y30-34' : return " AND (age='Y30'   OR age='Y31' OR age='Y32' OR age='Y33' OR age='Y34')";
            case 'Y35-39' : return " AND (age='Y35'   OR age='Y36' OR age='Y37' OR age='Y38' OR age='Y39')";
            case 'Y40-44' : return " AND (age='Y40'   OR age='Y41' OR age='Y42' OR age='Y43' OR age='Y44')";
            case 'Y45-49' : return " AND (age='Y45'   OR age='Y46' OR age='Y47' OR age='Y48' OR age='Y49')";
            case 'Y50-54' : return " AND (age='Y50'   OR age='Y51' OR age='Y52' OR age='Y53' OR age='Y54')";
            case 'Y55-59' : return " AND (age='Y55'   OR age='Y56' OR age='Y57' OR age='Y58' OR age='Y59')";
            case 'Y60-64' : return " AND (age='Y60'   OR age='Y61' OR age='Y62' OR age='Y63' OR age='Y64')";
            case 'Y65-69' : return " AND (age='Y65'   OR age='Y66' OR age='Y67' OR age='Y68' OR age='Y69')";
            case 'Y70-74' : return " AND (age='Y70'   OR age='Y71' OR age='Y72' OR age='Y73' OR age='Y74')";
            case 'Y75-79' : return " AND (age='Y75'   OR age='Y76' OR age='Y77' OR age='Y78' OR age='Y79')";
            case 'Y80-84' : return " AND (age='Y80'   OR age='Y81' OR age='Y82' OR age='Y83' OR age='Y84')";
            case 'Y85-89' : return " AND (age='Y85'   OR age='Y86' OR age='Y87' OR age='Y88' OR age='Y89')";
            case 'Y_GE90' : return " AND (age='Y90'   OR age='Y91' OR age='Y92' OR age='Y93' OR age='Y94'
                                     OR   age='Y95'   OR age='Y96' OR age='Y97' OR age='Y98' OR age='Y99'
                                     OR   age='Y_OPEN')";

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
                color: '#106097',
                yAxis: 0,
                data: [$value]
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
