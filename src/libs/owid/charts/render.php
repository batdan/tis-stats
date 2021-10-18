<?php
namespace owid\charts;

use tools\dbSingleton;

class render
{
    private static $dbh;

    private static $jsRender = '';


    public static function html($chartName, $title, $jsHightcharts, $backLink = true, $filterActiv)
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

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <!-- Boostrap -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

        <link href="/css/styles.css" rel="stylesheet" type="text/css">
    </head>

    <body>
        <div class="container-fluid">
            <div id="form">
                $chartFilter
            </div>

            <figure class="highcharts-figure">
                <div id="$chartName"></div>
                $backLinkLCH
            </figure>
        </div>

        <script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

        <!-- Latest compiled and minified JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

        <script type="text/javascript" src="//code.highcharts.com/highcharts.js"></script>
        <script type="text/javascript" src="//code.highcharts.com/modules/exporting.js"></script>
        <script src="https://code.highcharts.com/modules/offline-exporting.js"></script>
        <script src="https://code.highcharts.com/modules/export-data.js"></script>

        <script type="text/javascript">
            $jsHightcharts
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
            'country'   => false,
            'interval'  => false,
        ];

        $filterActiv = array_merge($filterActivDefault, $filterActiv);

        $chartFilters = '<div class="row" style="margin-bottom:10px;">';

        $chartFilters .= self::chartSelect();

        if ($filterActiv['country']) {
            $chartFilters .= self::chartFilterCountries();
        }

        if ($filterActiv['interval']) {
            $chartFilters .= self::chartFilterInterval();
        }

        $chartFilters .= '</div>';

        return $chartFilters;
    }


    private static function chartSelect()
    {
        $filter  = '<div class="form-group col-lg-3">';
        $filter .= '<label class="form-label" for="filter-chart">Sélection de graphiques C19</label>';
        $filter .= '<select id="filter-chart" class="custom-select">';

        $chartCollections = [
            'item-1'                                    => 'Tests PCR',
            'owid\charts\pcrCasesPerMillion'            => 'PCR : Cas positifs par million',
            'owid\charts\pcrPositivite'                 => 'PCR : taux de positivité',
            'owid\charts\pcrNewTestsPerThousand'        => 'PCR : tests quotidiens',
            'closeItem-1'                               => '',

            'item-2'                                    => 'Chiffres quotidiens',
            'owid\charts\newDeathsSmoothedPerMillion'   => 'Quotidien : décès',
            'closeItem-2'                               => '',

            'item-3'                                    => 'Chiffres hebdomadaires',
            'owid\charts\weeklyNewHpPerMillion'         => 'Hebdomadaire : hospitalisations',
            'owid\charts\weeklyNewReaPerMillion'        => 'Hebdomadaire : soins critiques',
            'closeItem-3'                               => '',

            'item-4'                                    => 'Occupation des hôpitaux',
            'owid\charts\nbOccupationHp'                => 'Occupation : hospitalisations',
            'owid\charts\nbOccupationRea'               => 'Occupation : soins critiques',
            'closeItem-4'                               => '',

            'item-5'                                    => 'Chiffres cumulés',
            'owid\charts\totalDeathPerMillion'          => 'Cumulé : décès',
            'owid\charts\totalCasesPerMillion'          => 'Cumulé : cas',
            'closeItem-5'                               => '',

            'item-6'                                    => 'Chiffres sur la vaccinations',
            'owid\charts\totalFirstVaccinatedPerHundred'=> 'Cumulé : partiellement vaccinés %',
            'owid\charts\totalVaccinatedPerHundred'     => 'Cumulé : totalement vaccinés %',
            'closeItem-6'                               => '',
        ];

        foreach ($chartCollections as $key => $text) {

            if (strstr($key, 'item')) {
                $filter .= '<optgroup label="' . $text . '">';
            } elseif (strstr($key, 'closeItem')) {
                $filter .= '</optgroup>';
            } else {

                $selected = '';
                if ($_SESSION['owid_filterChart'] == $key) {
                    $selected = ' selected="selected"';
                }

                $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
            }
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof

            $("#filter-chart").change( function() {
                $.post("/ajax/owid/filterChart.php",
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


    private static function chartFilterCountries()
    {
        $req = "SELECT ISO, location FROM owid_covid19 ORDER BY location ASC";
        $sql = self::$dbh->query($req);

        $countries = [];
        while ($res = $sql->fetch()) {
            $countries[$res->ISO] = $res->location;
        }

        $_SESSION['owid_filterCountry'];

        $filter  = '<div class="form-group col-lg-3" style="padding-left:0; padding-right:0;">';
        $filter .= '<label class="form-label col-lg-12" for="filter-country">Pays</label>';
        $filter .= '<select id="filter-country" class="selectpicker col-10" data-style="btn-default" multiple="multiple" data-live-search="true">';

        foreach ($countries as $iso => $location) {
            $selected = '';
            if (in_array($iso, $_SESSION['owid_filterCountry'])) {
                $selected = ' selected';
            }

            $filter .= '<option value="' . $iso . '"' . $selected  . '>' . $location . '</option>';
        }

        $filter .= '</select>';
        $filter .= '<button id="country-search" type="submit" class="col-2 btn btn-primary" style="border-radius:0 5px 5px 0; position:relative; left:-15px;">';
        $filter .= '<i class="fas fa-search" style="position:relative; left:-2px;"></i></button>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof

            // To style only selects with the my-select class
            $('#filter-country').selectpicker();

            $("#country-search").on('click', function() {
                $.post("/ajax/owid/filterCountries.php",
                {
                    filterCountries : $("#filter-country").val()
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
        $filter .= '<select id="filter-interval" class="custom-select">';

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
            if ($_SESSION['owid_filterInterval'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filter .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsRender .= <<<eof

            $("#filter-interval").change( function() {
                $.post("/ajax/owid/filterInterval.php",
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
}
