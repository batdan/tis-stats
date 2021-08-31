<?php
/**
 * Accès à la collection de cartes disponibles pour highcharts
 * https://code.highcharts.com/mapdata/
 */
namespace spf\maps;

use tools\dbSingleton;
use main\highChartsCommon;

class deathsPerMillion
{
    private $cache;
    private $dbh;

    private $regions;

    private $chartName;

    private $title;
    private $subTitle;

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

        $this->chartName = 'deathsPerMillion';

        $this->title    = "Nb actuel de décès covid-19";
        if ($_SESSION['spf_filterMapRatio'] == 1) {
            $this->title .= " par million d'habitants";
        }
        $this->title    = highChartsCommon::chartText($this->title);

        $this->subTitle = 'Source: Santé publique France | Données hospitalières';

        $this->legend   = "Décès";
        if ($_SESSION['spf_filterMapRatio'] == 1) {
            $this->legend .= " par million";
        }
    }


    private function getRegions()
    {
        $req = "SELECT region, iso, population FROM geo_reg2018";
        $sql = $this->dbh->query($req);

        $this->regions = [];
        while ($res = $sql->fetch()) {
            $this->regions[$res->region] = [
                'iso'        => $res->iso,
                'population' => $res->population,
            ];
        }
    }


    /**
     * Récupération des données de la statistique
     * en cache ou en BDD
     */
    public function getData()
    {
        $this->getRegions();

        $className = str_replace('\\', '_', get_class($this));
        $fileName  = date('Y-m-d_') . $className;

        $addReq = "";
        $addReqValues = [];
        $addReq .= " AND cl_age90 = :cl_age90";
        if (!empty($_SESSION['spf_filterMapAge']) && $_SESSION['spf_filterMapAge'] != '0') {
            $addReqValues[':cl_age90'] = $_SESSION['spf_filterMapAge'];
            $fileName .= '_age_' . $_SESSION['spf_filterMapAge'];
        } else {
            $addReqValues[':cl_age90'] = 0;
        }

        if (!empty($_SESSION['spf_filterMapInterval']) && $_SESSION['spf_filterMapInterval'] != 'all') {
            $fileName .= '_interval_' . $_SESSION['spf_filterMapInterval'];
        }

        switch ($_SESSION['spf_filterMapRatio']) {
            case 0 : $fileName .= '_total';     break;
            case 1 : $fileName .= '_million';   break;
        }

        if ($this->cache && $this->data = \main\cache::getCache($fileName)) {
            return json_encode($this->data);
        }

        $this->data = [];

        $req = "SELECT      reg, SUM(dc) AS mySum

                FROM        donnees_hp_cumule_age_covid19_reg_calc

                WHERE       jour = (SELECT jour FROM donnees_hp_cumule_age_covid19_reg_calc ORDER BY jour DESC LIMIT 1)
                $addReq

                GROUP BY    reg
                ORDER BY    reg ASC";

        $sql = $this->dbh->prepare($req);
        $sql->execute($addReqValues);
        $resEnd = $sql->fetchAll();

        $dataEnd = [];

        foreach ($resEnd as $res) {
            switch ($_SESSION['spf_filterMapRatio']) {
                case 0 :
                    $mySum = $res->mySum;
                    break;

                case 1 :
                    $mySum = round((1000000 / $this->regions[$res->reg]['population'] * $res->mySum));
                    break;
            }

            $dataEnd[] = [
                $this->regions[$res->reg]['iso'],
                floatval($mySum)
            ];
        }

        if (!empty($_SESSION['spf_filterMapInterval']) && $_SESSION['spf_filterMapInterval'] != 'all') {
            $addReq .= " AND jour = :jour";
            $addReqValues[':jour'] = $_SESSION['spf_filterMapInterval'];

            $req = "SELECT      reg, SUM(dc) AS mySum

                    FROM        donnees_hp_cumule_age_covid19_reg_calc

                    WHERE       1
                    $addReq

                    GROUP BY    reg
                    ORDER BY    reg ASC";

            $sql = $this->dbh->prepare($req);
            $sql->execute($addReqValues);
            $resDeb = $sql->fetchAll();

            $i=0;
            foreach ($resDeb as $res) {
                switch ($_SESSION['spf_filterMapRatio']) {
                    case 0 :
                        $mySum = $dataEnd[$i][1] - $res->mySum;
                        break;

                    case 1 :
                        $mySum = $dataEnd[$i][1] - round((1000000 / $this->regions[$res->reg]['population'] * $res->mySum));
                        break;
                }

                $this->data[] = [
                    $this->regions[$res->reg]['iso'],
                    floatval($mySum)
                ];

                $i++;
            }
        }

        if (!count($this->data)) {
            $this->data = $dataEnd;
        }

        // createCache
        \main\cache::createCache($fileName, $this->data);

        return json_encode($this->data);
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $credit = highChartsCommon::creditMapsLCH();

        $this->highChartsJs = <<<eof
            Highcharts.getJSON('/charts/spf/covid19-maps-json.php?{$this->chartName}', function (data) {

                // Prevent logarithmic errors in color calulcation
                data.forEach(function (p) {
                    p.value = (p.value < 1 ? 1 : p.value);
                });

                Highcharts.mapChart('{$this->chartName}', {

                    $credit

                    chart: {
                        map: 'countries/fr/fr-all'
                    },

                    title: {
                        text: '{$this->title}'
                    },

                    subtitle: {
                        text: '{$this->subTitle}'
                    },

                    mapNavigation: {
                        enabled: true,
                        buttonOptions: {
                            verticalAlign: 'bottom'
                        }
                    },

                    colorAxis: {
                        // min: 0
                    },

                    series: [{
                        data: data,
                        name: '{$this->legend}',
                        states: {
                            hover: {
                                color: '#BADA55'
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            format: '{point.name}'
                        }
                    }, {
                        name: 'Separators',
                        type: 'mapline',
                        // data: Highcharts.geojson(Highcharts.maps['countries/fr/fr-all'], 'mapline'),
                        data: data,
                        color: 'silver',
                        nullColor: 'silver',
                        showInLegend: false,
                        enableMouseTracking: false
                    }]
                });
            });
eof;
    }


    /**
     * Redu HTML
     */
    public function render()
    {
        $this->highChartsJs();

        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [
            'interval'  => true,
            'age'       => true,
            'ratio'     => true,
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
