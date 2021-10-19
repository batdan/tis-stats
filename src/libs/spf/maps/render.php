<?php
/**
 * Accès à la collection de cartes disponibles pour highcharts
 * https://code.highcharts.com/mapdata/
 */
namespace spf\maps;

use tools\dbSingleton;

class render
{
    private static $dbh;

    private static $jsRender = '';


    public static function html($chartName, $title, $js, $backLink = true, $filterActiv)
    {
        self::$dbh = dbSingleton::getInstance();

        $backLinkLCH = self::backLink($backLink);
        $chartFilter = self::chartFilters($filterActiv);

        $jsRender = self::$jsRender;
        $md5Css = md5_file( __DIR__ . '/../../../css/styles.css');

        return <<<eof
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>$title</title>

        <link rel="icon" href="https://www.lachainehumaine.com/wp-content/uploads/2021/07/cropped-logo-1-60x60.png" sizes="32x32" />
        <link rel="icon" href="https://www.lachainehumaine.com/wp-content/uploads/2021/07/cropped-logo-1-300x300.png" sizes="192x192" />
        <link rel="apple-touch-icon" href="https://www.lachainehumaine.com/wp-content/uploads/2021/07/cropped-logo-1-300x300.png" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
        <link href="/css/styles.css?$md5Css" rel="stylesheet" type="text/css">

        <script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>

        <!-- Flag sprites service provided by Martijn Lafeber, https://github.com/lafeber/world-flags-sprite/blob/master/LICENSE -->
        <link href="//github.com/downloads/lafeber/world-flags-sprite/flags32.css" rel="stylesheet" type="text/css">

        <style>
            #$chartName {
                height: 650px;
                width: 100%;
                min-width: 310px;
                max-width: 1900px;
                margin: 0 auto;
            }
            .loading {
                margin-top: 10em;
                text-align: center;
                color: gray;
            }
        </style>

    </head>

    <body>
        <div class="container-fluid">
            <div id="form">
                $chartFilter
            </div>

            <figure class="highcharts-figure">
                <div id="$chartName" class="maps"></div>
                $backLinkLCH
            </figure>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script>
        <script src="https://code.highcharts.com/maps/highmaps.js"></script>
        <script src="https://code.highcharts.com/maps/modules/data.js"></script>
        <script src="https://code.highcharts.com/maps/modules/exporting.js"></script>
        <script src="https://code.highcharts.com/maps/modules/offline-exporting.js"></script>
        <script src="https://code.highcharts.com/mapdata/countries/fr/fr-all.js"></script>

        <script type="text/javascript">
            $js
            $jsRender

            $(function() {
                $('body').hide().fadeIn('slow');
            });
        </script>
    </body>
</html>
eof;
    }


    public static function backLink($backLink)
    {
        $backLinkLCH = '';

        if ($backLink) {
            $backLinkLCH = <<<eof
            <p style="text-align:center;">
                <a class="lch" href="https://www.lachainehumaine.com" target="_blank"><img class="logo" src="/img/cropped-logo-1-150x150.png" alt="La chaîne humaine"></a>
            </p>
            <p style="text-align:center;">
                <a class="lch" href="https://www.lachainehumaine.com" target="_blank"><i class="fas fa-external-link-alt"></i> La chaîne humaine</a>
            </p>
eof;
        }

        return $backLinkLCH;
    }


    public static function chartFilters($filterActiv)
    {
        $filterActivDefault = [
            'interval'  => false,
            'age'       => false,
            'age2'      => false,
            'vaccin'    => false,
            'ratio'     => false,
        ];


        $filterActiv = array_merge($filterActivDefault, $filterActiv);

        $chartFilters = '<div class="row" style="margin-bottom:10px;">';

        $chartFilters .= self::chartSelect();

        if ($filterActiv['interval']) {
            $chartFilters .= self::chartFilterInterval();
        }

        if ($filterActiv['age']) {
            $chartFilters .= self::chartFilterAge();
        }

        if ($filterActiv['age2']) {
            $chartFilters .= self::chartFilterAge2();
        }

        if ($filterActiv['vaccin']) {
            $chartFilters .= self::chartFilterVaccin();
        }

        if ($filterActiv['ratio']) {
            $chartFilters .= self::chartFilterRatio();
        }

        $chartFilters .= '</div>';

        return $chartFilters;
    }


    private static function chartSelect()
    {
        $filter  = '<div class="form-group col-lg-3">';
        $filter .= '<label class="form-label" for="filter-map">Sélection de la carte C19</label>';
        $filter .= '<select id="filter-map" class="form-select">';

        $chartCollections = [
            'item-1'                                        => 'Tests PCR',
            'spf\maps\pcrPositivite'                        => 'PCR : taux de positivité',
            'spf\maps\pcrNbTestes'                          => 'PCR : Nb de testés',
            'spf\maps\pcrCas'                               => 'PCR : Nb de cas',
            'closeItem-1'                                   => '',

            'item-2'                                        => 'Occupation des hôpitaux',
            'spf\maps\nbOccupationHp'                       => 'Occupation : hospitalisations',
            'spf\maps\nbOccupationRea'                      => 'Occupation : soins critiques',
            'closeItem-2'                                   => '',

            'item-3'                                        => 'Chiffres cumulés',
            'spf\maps\deathsPerMillion'                     => 'Cumulé : décès',
            'closeItem-3'                                   => '',

            'item-4'                                        => 'Chiffres sur la vaccinations',
            'spf\maps\totalFirstVaccinatedPerHundred'       => 'Vaccinations 1 dose',
            'spf\maps\totalVaccinatedPerHundred'            => 'Vaccinations complètes',
            'closeItem-4'                                   => '',
        ];

        foreach ($chartCollections as $key => $text) {

            if (strstr($key, 'item')) {
                $filter .= '<optgroup label="' . $text . '">';
            } elseif (strstr($key, 'closeItem')) {
                $filter .= '</optgroup>';
            } else {

                $selected = '';
                if ($_SESSION['spf_filterMap'] == $key) {
                    $selected = ' selected="selected"';
                }

                $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
            }
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-map").change( function() {
                $.post("/ajax/spf/filterMap.php",
                {
                    filterMap : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
eof;

        return $filter;
    }


    private static function chartFilterInterval()
    {
        $filter  = '<div class="form-group col-lg-3">';
        $filter .= '<label class="form-label" for="filter-interval">Période</label>';
        $filter .= '<select id="filter-interval" class="form-select">';

        $d = new \dateTime();
        $interval = new \DateInterval('P1M');
        $d->sub($interval);
        $p1m = $d->format('Y-m-d');

        $d = new \dateTime();
        $interval = new \DateInterval('P3M');
        $d->sub($interval);
        $p3m = $d->format('Y-m-d');

        $d = new \dateTime();
        $interval = new \DateInterval('P6M');
        $d->sub($interval);
        $p6m = $d->format('Y-m-d');

        $d = new \dateTime();
        $interval = new \DateInterval('P12M');
        $d->sub($interval);
        $p12m = $d->format('Y-m-d');

        $chartInterval = [
            'all'   => 'Depuis le début',
            $p1m    => 'Depuis 1 mois',
            $p3m    => 'Depuis 3 mois',
            $p6m    => 'Depuis 6 mois',
            $p12m   => 'Depuis 12 mois',
        ];

        foreach ($chartInterval as $chart => $text) {
            $selected = '';
            if ($_SESSION['spf_filterMapInterval'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-interval").change( function() {
                $.post("/ajax/spf/filterMapInterval.php",
                {
                    filterMapInterval : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
eof;

        return $filter;
    }


    private static function chartFilterAge()
    {
        $filter  = '<div class="form-group col-lg-3">';
        $filter .= '<label class="form-label" for="filter-age">Age</label>';
        $filter .= '<select id="filter-age" class="form-select">';

        $chartInterval = [
            '0'     => 'Tous les âges',
            '09'    => '0 à 9 ans',
            '19'    => '10 à 19 ans',
            '29'    => '20 à 29 ans',
            '39'    => '30 à 39 ans',
            '49'    => '40 à 49 ans',
            '59'    => '50 à 59 ans',
            '69'    => '60 à 69 ans',
            '79'    => '70 à 79 ans',
            '89'    => '80 à 89 ans',
            '90'    => '90 ans et plus',
        ];

        foreach ($chartInterval as $chart => $text) {
            $selected = '';
            if ($_SESSION['spf_filterMapAge'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-age").change( function() {
                $.post("/ajax/spf/filterMapAge.php",
                {
                    filterMapAge : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
eof;

        return $filter;
    }


    private static function chartFilterAge2()
    {
        $filter  = '<div class="form-group col-lg-3">';
        $filter .= '<label class="form-label" for="filter-age2">Age</label>';
        $filter .= '<select id="filter-age2" class="form-select">';

        $chartInterval = [
            '0'  => 'Tous les âges',
            '04' => '0 à 4 ans',
            '09' => '5 à 9 ans',
            '11' => '10 à 11 ans',
            '17' => '12 à 17 ans',
            '24' => '18 à 24 ans',
            '29' => '25 à 29 ans',
            '39' => '30 à 39 ans',
            '49' => '40 à 49 ans',
            '59' => '50 à 59 ans',
            '69' => '60 à 69 ans',
            '74' => '70 à 74 ans',
            '79' => '75 à 79 ans',
            '80' => '80 ans et plus',
        ];

        foreach ($chartInterval as $chart => $text) {
            $selected = '';
            if ($_SESSION['spf_filterMapAge2'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-age2").change( function() {
                $.post("/ajax/spf/filterMapAge2.php",
                {
                    filterMapAge2 : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
eof;

        return $filter;
    }


    private static function chartFilterVaccin()
    {
        $filter  = '<div class="form-group col-lg-3">';
        $filter .= '<label class="form-label" for="filter-vaccin">Vaccin</label>';
        $filter .= '<select id="filter-vaccin" class="form-select">';

        $chartInterval = [
            0 => 'Tous Vaccins',
            1 => 'Pfizer',
            2 => 'Moderna',
            3 => 'AstraZeneka',
            4 => 'Janssen',
        ];

        foreach ($chartInterval as $chart => $text) {
            $selected = '';
            if ($_SESSION['spf_filterMapVaccin'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-vaccin").change( function() {
                $.post("/ajax/spf/filterMapVaccin.php",
                {
                    filterMapVaccin : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
eof;

        return $filter;
    }




    private static function chartFilterRatio()
    {
        $filter  = '<div class="form-group col-lg-3">';
        $filter .= '<label class="form-label" for="filter-ratio">Total, ratio</label>';
        $filter .= '<select id="filter-ratio" class="form-select">';

        $chartInterval = [
            0 => 'Total',
            1 => 'Par million',
        ];

        foreach ($chartInterval as $chart => $text) {
            $selected = '';
            if ($_SESSION['spf_filterMapRatio'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-ratio").change( function() {
                $.post("/ajax/spf/filterMapRatio.php",
                {
                    filterMapRatio : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
eof;

        return $filter;
    }
}
