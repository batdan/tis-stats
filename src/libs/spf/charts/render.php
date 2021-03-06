<?php
namespace spf\charts;

use tools\dbSingleton;

class render
{
    private static $dbh;

    private static $jsRender = '';


    public static function html($chartName, $title, $js, $backLink = true, $filterActiv, $warning = '')
    {
        self::$dbh = dbSingleton::getInstance();

        $backLinkLCH = self::backLink($backLink);
        $chartFilter = self::chartFilters($filterActiv);

        $jsRender = self::$jsRender;

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
        <link href="/css/styles.css" rel="stylesheet" type="text/css">

        <script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
    </head>

    <body style="margin:0;">
        <div class="container-fluid">

            <div id="form">
                $chartFilter
            </div>

            <div class="warning">
                $warning
            </div>

            <figure class="highcharts-figure">
                <div id="$chartName"></div>
                $backLinkLCH
            </figure>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script>
        <script type="text/javascript" src="//code.highcharts.com/highcharts.js"></script>
        <script type="text/javascript" src="//code.highcharts.com/modules/exporting.js"></script>

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
                <a class="lch" href="https://www.lachainehumaine.com" target="_blank"><img class="logo" src="/img/cropped-logo-1-150x150.png" alt="La cha??ne humaine"></a>
            </p>
            <p style="text-align:center;">
                <a class="lch" href="https://www.lachainehumaine.com" target="_blank"><i class="fas fa-external-link-alt"></i> La cha??ne humaine</a>
            </p>
eof;
        }

        return $backLinkLCH;
    }


    public static function chartFilters($filterActiv)
    {
        $filterActivDefault = [
            'region'    => false,
            'interval'  => false,
            'age'       => false,
            'age2'      => false,
            'vaccin'    => false,
            'unite'     => false,
        ];

        $filterActiv = array_merge($filterActivDefault, $filterActiv);

        $countFilter=1;
        foreach ($filterActiv as $filter) {
            if ($filter) $countFilter++;
        }

        $classFilter = ($countFilter > 4) ? 'col-lg-2' : 'col-lg-3';

        $chartFilters = '<div class="row" style="margin-bottom:10px;">';

        $chartFilters .= self::chartSelect();

        if ($filterActiv['region']) {
            $chartFilters .= self::chartFilterRegion();
        }

        if ($filterActiv['interval']) {
            $chartFilters .= self::chartFilterInterval($classFilter);
        }

        if ($filterActiv['age']) {
            $chartFilters .= self::chartFilterAge($classFilter);
        }

        if ($filterActiv['age2']) {
            $chartFilters .= self::chartFilterAge2($classFilter);
        }

        if ($filterActiv['vaccin']) {
            $chartFilters .= self::chartFilterVaccin($classFilter);
        }

        if ($filterActiv['unite']) {
            $chartFilters .= self::chartFilterUnite($classFilter);
        }

        $chartFilters .= '</div>';

        return $chartFilters;
    }


    private static function chartSelect()
    {
        $filter  = '<div class="form-group col-lg-3">';
        $filter .= '<label class="form-label" for="filter-chart">S??lection de graphiques C19</label>';
        $filter .= '<select id="filter-chart" class="form-select">';

        $chartCollections = [
            'item-1'                                    => 'Tests PCR',
            'spf\charts\pcrPositivite'                  => 'PCR : taux de positivit??',
            'spf\charts\pcrCumulTests'                  => 'PCR : nb de tests r??alis??s',
            'closeItem-1'                               => '',

            'item-2'                                    => 'Chiffres quotidiens',
            'spf\charts\quotidienEntreesHp'             => 'Quotidien : hospitalisations',
            'spf\charts\quotidienEntreesRea'            => 'Quotidien : soins critiques',
            'spf\charts\quotidienDeces'                 => 'Quotidien : d??c??s',
            'spf\charts\quotidienRad'                   => 'Quotidien : retours ?? domicile',
            'closeItem-2'                               => '',

            'item-3'                                    => 'Occupation des h??pitaux',
            'spf\charts\nbOccupationHp'                 => 'Occupation : hospitalisations',
            'spf\charts\nbOccupationRea'                => 'Occupation : soins critiques',
            'closeItem-3'                               => '',

            'item-4'                                    => 'Chiffres cumul??s',
            'spf\charts\nbCumuleDeces'                  => 'Cumul?? : d??c??s',
            'spf\charts\nbCumuleDecesAge'               => 'Cumul?? : d??c??s par ??ge',
            'spf\charts\nbCumuleRad'                    => 'Cumul?? : retours ?? domicile',
            'closeItem-4'                               => '',

            'item-5'                                    => 'Chiffres sur la vaccinations',
            'spf\charts\quotidienVaccinationAge'        => 'Quotidien : vaccinations par ??ge',
            'spf\charts\quotidienVaccinationVaccin'     => 'Quotidien : vaccinations par vaccin',
            'spf\charts\nbCumuleVaccinationAge'         => 'Cumul?? : vaccinations par ??ge',
            'spf\charts\nbCumuleVaccinationVaccin'      => 'Cumul?? : vaccinations par vaccin',
            'closeItem-5'                               => '',
        ];

        foreach ($chartCollections as $key => $text) {

            if (strstr($key, 'item')) {
                $filter .= '<optgroup label="' . $text . '">';
            } elseif (strstr($key, 'closeItem')) {
                $filter .= '</optgroup>';
            } else {

                $selected = '';
                if ($_SESSION['spf_filterChart'] == $key) {
                    $selected = ' selected="selected"';
                }

                $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
            }
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-chart").change( function() {
                $.post("/ajax/spf/filterChart.php",
                {
                    filterChart : $(this).find(":selected").val()
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


    private static function chartFilterRegion()
    {
        $filter  = '<div class="form-group col-lg-3">';
        $filter .= '<label class="form-label" for="filter-region">R??gion</label>';
        $filter .= '<select id="filter-region" class="form-select">';

        $req = "SELECT region, nccenr FROM geo_reg2018 ORDER BY nccenr";
        $sql = self::$dbh->query($req);

        $selected = '';
        if ($_SESSION['spf_filterRegionId'] == 0) {
            $selected = ' selected="selected"';
        }

        $filter .= '<optgroup label="Pays">';
        $filter .= '<option value="0"' . $selected  . '>FRANCE</option>';
        $filter .= '</optgroup>';

        $filter .= '<optgroup label="R??gions">';
        while ($res = $sql->fetch()) {
            $selected = '';
            if ($_SESSION['spf_filterRegionId'] == $res->region) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $res->region . '"' . $selected  . '>' . $res->nccenr . '</option>';
        }
        $filter .= '</optgroup>';

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-region").change( function() {
                $.post("/ajax/spf/filterRegion.php",
                {
                    filterRegion : $(this).find(":selected").val()
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


    private static function chartFilterInterval($classFilter)
    {
        $filter  = '<div class="form-group ' . $classFilter . '">';
        $filter .= '<label class="form-label" for="filter-interval">P??riode</label>';
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

        $select = [
            'all'   => 'Depuis le d??but',
            $p1m    => 'Depuis 1 mois',
            $p3m    => 'Depuis 3 mois',
            $p6m    => 'Depuis 6 mois',
            $p12m   => 'Depuis 12 mois',
        ];

        foreach ($select as $key  => $text) {
            $selected = '';
            if ($_SESSION['spf_filterInterval'] == $key) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-interval").change( function() {
                $.post("/ajax/spf/filterInterval.php",
                {
                    filterInterval : $(this).find(":selected").val()
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


    private static function chartFilterAge($classFilter)
    {
        $filter  = '<div class="form-group ' . $classFilter . '">';
        $filter .= '<label class="form-label" for="filter-age">Age</label>';
        $filter .= '<select id="filter-age" class="form-select">';

        $select = [
            '0'     => 'Tous les ??ges',
            '09'    => '0 ?? 9 ans',
            '19'    => '10 ?? 19 ans',
            '29'    => '20 ?? 29 ans',
            '39'    => '30 ?? 39 ans',
            '49'    => '40 ?? 49 ans',
            '59'    => '50 ?? 59 ans',
            '69'    => '60 ?? 69 ans',
            '79'    => '70 ?? 79 ans',
            '89'    => '80 ?? 89 ans',
            '90'    => '90 ans et plus',
        ];

        foreach ($select as $key  => $text) {
            $selected = '';
            if ($_SESSION['spf_filterAge'] == $key) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-age").change( function() {
                $.post("/ajax/spf/filterAge.php",
                {
                    filterAge : $(this).find(":selected").val()
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


    private static function chartFilterAge2($classFilter)
    {
        $filter  = '<div class="form-group ' . $classFilter . '">';
        $filter .= '<label class="form-label" for="filter-age2">Age</label>';
        $filter .= '<select id="filter-age2" class="form-select">';

        $select = [
            '0'  => 'Tous les ??ges',
            '04' => '0 ?? 4 ans',
            '09' => '5 ?? 9 ans',
            '11' => '10 ?? 11 ans',
            '17' => '12 ?? 17 ans',
            '24' => '18 ?? 24 ans',
            '29' => '25 ?? 29 ans',
            '39' => '30 ?? 39 ans',
            '49' => '40 ?? 49 ans',
            '59' => '50 ?? 59 ans',
            '64' => '60 ?? 64 ans',
            '69' => '65 ?? 69 ans',
            '74' => '70 ?? 74 ans',
            '79' => '75 ?? 79 ans',
            '80' => '80 ans et plus',
        ];

        foreach ($select as $key  => $text) {
            $selected = '';
            if ($_SESSION['spf_filterAge2'] == $key) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-age2").change( function() {
                $.post("/ajax/spf/filterAge2.php",
                {
                    filterAge2 : $(this).find(":selected").val()
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


    private static function chartFilterVaccin($classFilter)
    {
        $filter  = '<div class="form-group ' . $classFilter . '">';
        $filter .= '<label class="form-label" for="filter-vaccin">Vaccin</label>';
        $filter .= '<select id="filter-vaccin" class="form-select">';

        $select = [
            0 => 'Tous Vaccins',
            1 => 'Pfizer',
            2 => 'Moderna',
            3 => 'AstraZeneka',
            4 => 'Janssen',
        ];

        foreach ($select as $key  => $text) {
            $selected = '';
            if ($_SESSION['spf_filterVaccin'] == $key) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-vaccin").change( function() {
                $.post("/ajax/spf/filterVaccin.php",
                {
                    filterVaccin : $(this).find(":selected").val()
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


    private static function chartFilterUnite($classFilter)
    {
        $filter  = '<div class="form-group ' . $classFilter . '">';
        $filter .= '<label class="form-label" for="filter-unite">Unit??</label>';
        $filter .= '<select id="filter-unite" class="form-select">';

        $select = [
            'quantity'  => 'Nombre',
            'percent'   => 'pourcentage',
        ];

        foreach ($select as $key  => $text) {
            $selected = '';
            if ($_SESSION['spf_filterUnite'] == $key) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-unite").change( function() {
                $.post("/ajax/spf/filterUnite.php",
                {
                    filterUnite : $(this).find(":selected").val()
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
