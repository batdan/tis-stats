<?php
namespace eurostat\charts;

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
            'charts'    => [true,  'col-lg-3'],
            'countries' => [false, 'col-lg-3'],
            'year1'     => [false, 'col-lg-3'],
            'year2'     => [false, 'col-lg-3'],
            'sex'       => [false, 'col-lg-3'],
            'age'       => [false, 'col-lg-3'],
        ];

        $filterActiv = array_merge($filterActivDefault, $filterActiv);

        $chartFilters = '<div class="row" style="margin-bottom:10px;">';

        $chartFilters .= self::chartSelect($filterActiv['charts'][1]);

        if ($filterActiv['countries'][0]) {
            $chartFilters .= self::chartFilterCountries($filterActiv['countries'][1]);
        }

        if ($filterActiv['year1'][0]) {
            $chartFilters .= self::chartFilterYear1($filterActiv['year1'][1]);
        }

        if ($filterActiv['year2'][0]) {
            $chartFilters .= self::chartFilterYear2($filterActiv['year2'][1]);
        }

        if ($filterActiv['sex'][0]) {
            $chartFilters .= self::chartFilterSex($filterActiv['sex'][1]);
        }

        if ($filterActiv['age'][0]) {
            $chartFilters .= self::chartFilterAge($filterActiv['age'][1]);
        }

        $chartFilters .= '</div>';

        return $chartFilters;
    }


    private static function chartSelect($class)
    {
        $filter  = '<div class="form-group ' . $class .'">';
        $filter .= '<label class="form-label" for="filter-chart">Sélection de graphiques C19</label>';
        $filter .= '<select id="filter-chart" class="form-select">';

        $chartCollections = [
            'item-1'                                    => 'Décès',
            'eurostat\charts\deces'                     => 'Décés toutes causes confondues',
            'eurostat\charts\decesStandardises'         => 'Décés toutes causes confondues standardisés',
            'closeItem-1'                               => '',

            'item-2'                                    => 'Population',
            'eurostat\charts\pyramideAges'              => 'Pyramide des âges',
            'closeItem-2'                               => '',
        ];

        foreach ($chartCollections as $key => $text) {

            if (strstr($key, 'item')) {
                $filter .= '<optgroup label="' . $text . '">';
            } elseif (strstr($key, 'closeItem')) {
                $filter .= '</optgroup>';
            } else {

                $selected = '';
                if ($_SESSION['eurostat_filterChart'] == $key) {
                    $selected = ' selected="selected"';
                }

                $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
            }
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-chart").change( function() {
                $.post("/ajax/eurostats/filterChart.php",
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


    private static function chartFilterCountries($class)
    {
        $filter  = '<div class="form-group ' . $class .'">';
        $filter .= '<label class="form-label" for="filter-country">Pays</label>';
        $filter .= '<select id="filter-country" class="form-select">';

        $req = "SELECT      pays, iso_3166_1_alpha_2
                FROM        base_countries
                WHERE       eurostat_activ = 1
                ORDER BY    pays";
        $sql = self::$dbh->query($req);

        while ($res = $sql->fetch()) {
            $selected = '';
            if ($_SESSION['eurostat_filterCountry'] == $res->iso_3166_1_alpha_2) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $res->iso_3166_1_alpha_2 . '"' . $selected  . '>' . $res->pays . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-country").change( function() {
                $.post("/ajax/eurostat/filterCountries.php",
                {
                    filterCountry : $(this).find(":selected").val()
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


    private static function chartFilterYear1($class)
    {
        $filter  = '<div class="form-group ' . $class .'">';
        $filter .= '<label class="form-label" for="filter-year1">Année</label>';
        $filter .= '<select id="filter-year1" class="form-select">';

        $className = str_replace('\\', '_', get_called_class());
        $fileName  = date('Y-m-d_') . $className . '_filterYear1';

        if ($years = \main\cache::getCache($fileName)) {
            //
        } else {
            $req = "SELECT MIN(year) AS minYear, MAX(year) AS maxYear FROM eurostat_demo_pjan";
            $sql = self::$dbh->query($req);
            $res = $sql->fetch();
            $min = $res->minYear;
            $max = $res->maxYear;

            $req = "SELECT MIN(year) AS minYear, MAX(year) AS maxYear FROM eurostat_demo_magec";
            $sql = self::$dbh->query($req);
            $res = $sql->fetch();
            $min = ($res->minYear > $min) ? $res->minYear : $min;
            $max = ($res->maxYear < $max) ? $res->maxYear : $max;

            $years = [
                'min' => $min,
                'max' => $max,
            ];

            // createCache
            \main\cache::createCache($fileName, $years);
        }

        $yearsRange = range($years['min'], $years['max']);
        rsort($yearsRange);

        foreach ($yearsRange as $year) {
            $selected = '';
            if ($_SESSION['eurostat_filterYear1'] == $year) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $year . '"' . $selected  . '>' . $year . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-year1").change( function() {
                $.post("/ajax/eurostat/filterYear1.php",
                {
                    filterYear1 : $(this).find(":selected").val()
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


    private static function chartFilterYear2($class)
    {
        $filter  = '<div class="form-group ' . $class .'">';
        $filter .= '<label class="form-label" for="filter-year2">Année</label>';
        $filter .= '<select id="filter-year2" class="form-select">';

        $className = str_replace('\\', '_', get_called_class());
        $fileName  = date('Y-m-d_') . $className . '_filterYear2';

        if ($years = \main\cache::getCache($fileName)) {
            //
        } else {
            $req = "SELECT MIN(year) AS minYear, MAX(year) AS maxYear FROM eurostat_demo_pjan";
            $sql = self::$dbh->query($req);
            $res = $sql->fetch();
            $min = $res->minYear;
            $max = $res->maxYear;

            $req = "SELECT SUBSTR(MIN(year_week),1,4) AS minYear, SUBSTR(MAX(year_week),1,4) AS maxYear FROM eurostat_demo_r_mwk_05";
            $sql = self::$dbh->query($req);
            $res = $sql->fetch();
            $min = ($res->minYear > $min) ? $res->minYear : $min;
            $max = ($res->maxYear < $max) ? $res->maxYear : $max;

            $years = [
                'min' => $min,
                'max' => $max,
            ];

            // createCache
            \main\cache::createCache($fileName, $years);
        }

        $yearsRange = range($years['min'], $years['max']);

        foreach ($yearsRange as $year) {
            $selected = '';
            if ($_SESSION['eurostat_filterYear2'] == $year) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $year . '"' . $selected  . '>' . $year . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-year2").change( function() {
                $.post("/ajax/eurostat/filterYear2.php",
                {
                    filterYear2 : $(this).find(":selected").val()
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


    private static function chartFilterSex($class)
    {
        $filter  = '<div class="form-group ' . $class .'">';
        $filter .= '<label class="form-label" for="filter-sex">Sexe</label>';
        $filter .= '<select id="filter-sex" class="form-select">';

        $select = [
            'T' => 'Femmes & hommes',
            'F' => 'Femmes',
            'M' => 'Hommes',
        ];

        foreach ($select as $key  => $text) {
            $selected = '';
            if ($_SESSION['eurostat_filterSex'] == $key) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-sex").change( function() {
                $.post("/ajax/eurostat/filterSex.php",
                {
                    filterSex : $(this).find(":selected").val()
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


    private static function chartFilterAge($class)
    {
        $filter  = '<div class="form-group ' . $class .'">';
        $filter .= '<label class="form-label" for="filter-age">Age</label>';
        $filter .= '<select id="filter-age" class="form-select">';

        // Y_LT5 : Less than 5 years | http://dd.eionet.europa.eu/vocabularyconcept/eurostat/agechild/Y_LT5/view
        // Y_GE90 : 90 years or over | http://dd.eionet.europa.eu/vocabularyconcept/eurostat/agechild/Y_GE90/view
        $select = [
            'TOTAL'  => 'Tous les âges',
            'Y_LT5'  => '0 à 4 ans',
            'Y5-9'   => '5 à 9 ans',
            'Y10-14' => '10 à 14 ans',
            'Y15-19' => '15 à 19 ans',
            'Y20-24' => '20 à 24 ans',
            'Y25-29' => '25 à 29 ans',
            'Y30-34' => '30 à 34 ans',
            'Y35-39' => '35 à 39 ans',
            'Y40-44' => '40 à 44 ans',
            'Y45-49' => '45 à 49 ans',
            'Y50-54' => '50 à 54 ans',
            'Y55-59' => '55 à 59 ans',
            'Y60-64' => '60 à 64 ans',
            'Y65-69' => '65 à 69 ans',
            'Y70-74' => '70 à 74 ans',
            'Y75-79' => '75 à 79 ans',
            'Y80-84' => '80 à 84 ans',
            'Y85-89' => '85 à 89 ans',
            'Y_GE90' => '90 ans et plus',
        ];

        foreach ($select as $key => $text) {
            $selected = '';
            if ($_SESSION['eurostat_filterAge'] == $key) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-age").change( function() {
                $.post("/ajax/eurostat/filterAge.php",
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
}
