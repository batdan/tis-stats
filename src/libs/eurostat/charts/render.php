<?php
namespace eurostat\charts;

use tools\dbSingleton;
use eurostat\main\tools;

class render
{
    private static $dbh;

    private static $jsRender = '';


    public static function html($chartName, $title, $js, $backLink = true, $filterActiv = [], $warning = '')
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
    </head>

    <body>
        <div class="container-fluid">

            <div id="form">
                $chartFilter
            </div>

            <div class="warning">
                $warning
            </div>

            <figure class="highcharts-figure" align="center">
                <div id="$chartName"><img src="/img/ajax-loader.gif" style="margin:200px 0;"></div>
                $backLinkLCH
            </figure>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script>
        <script type="text/javascript" src="//code.highcharts.com/highcharts.js"></script>
        <script type="text/javascript" src="//code.highcharts.com/modules/exporting.js"></script>

        <script type="text/javascript">

            $(function() {
                $('body').hide().fadeIn('slow');
            });

            $js
            $jsRender
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
            'unit'      => [false, 'col-lg-3'],
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

        if ($filterActiv['unit'][0]) {
            $chartFilters .= self::chartFilterUnit($filterActiv['unit'][1]);
        }

        $chartFilters .= '</div>';

        return $chartFilters;
    }


    private static function chartSelect($class)
    {
        $filter  = '<div class="form-group ' . $class .'">';
        $filter .= '<label class="form-label" for="filter-chart">Sélection de graphiques</label>';
        $filter .= '<select id="filter-chart" class="form-select">';

        $chartCollections = [
            'item-1'                                    => 'Décès toutes causes confondues (TTC)',
            'eurostat\charts\deces'                     => 'Décés TTC',
            'eurostat\charts\decesStandardises'         => 'Décés TTC standardisés',
            'eurostat\charts\decesHebdoStandardises'    => 'Décés TTC hebdomadaires standardisés',
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
                $.post("/ajax/eurostat/filterChart.php",
                {
                    filterChart : $(this).find(":selected").val()
                },
                function success(data)
                {
                    $('#decesHebdoStandardises').empty();
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

        foreach (tools::getCountries() as $iso2 => $country) {
            $selected = '';
            if ($_SESSION['eurostat_filterCountry'] == $iso2) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $iso2 . '"' . $selected . '>' . $country . '</option>';
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
                    $('#decesHebdoStandardises').empty();
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

            $addReq = "";
            $addReqValues = [];
            if (!empty($_SESSION['eurostat_filterCountry'])) {
                $addReq .= " AND geotime = :geotime";
                $addReqValues[':geotime'] = $_SESSION['eurostat_filterCountry'];
                $fileName .= '_country_' . $_SESSION['eurostat_filterCountry'];
            }

            $req = "SELECT  MIN(year) AS minYear, MAX(year) AS maxYear
                    FROM    eurostat_demo_pjan_opti
                    WHERE   age = 'Y_LT5'
                    $addReq";
            $sql = self::$dbh->prepare($req);
            $sql->execute($addReqValues);
            $res = $sql->fetch();
            $min = $res->minYear;
            $max = $res->maxYear;

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
                    $('#decesHebdoStandardises').empty();
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
            $req = "SELECT MIN(year) AS minYear, MAX(year) AS maxYear FROM eurostat_demo_pjan_opti";
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
                    $('#decesHebdoStandardises').empty();
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

        foreach (tools::getSex() as $key  => $text) {
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
                    $('#decesHebdoStandardises').empty();
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

        foreach (tools::rangeFilterAge() as $key => $text) {
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
                    $('#decesHebdoStandardises').empty();
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
eof;

        return $filter;
    }


    private static function chartFilterUnit($class)
    {
        $filter  = '<div class="form-group ' . $class .'">';
        $filter .= '<label class="form-label" for="filter-unit">Unité</label>';
        $filter .= '<select id="filter-unit" class="form-select">';

        $units = [
            'percent' => 'Pourcentage',
            'number'  => 'Nombre',
        ];

        foreach ($units as $key => $text) {
            $selected = '';
            if ($_SESSION['eurostat_filterUnit'] == $key) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof
            $("#filter-unit").change( function() {
                $.post("/ajax/eurostat/filterUnit.php",
                {
                    filterUnit : $(this).find(":selected").val()
                },
                function success(data)
                {
                    $('#decesHebdoStandardises').empty();
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
eof;

        return $filter;
    }
}
