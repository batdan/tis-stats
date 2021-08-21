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

    <body style="margin:0;">
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
        <script type="text/javascript">
            $jsHightcharts
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
        $filterChart  = '<div class="form-group col-lg-3">';
        $filterChart .= '<label class="form-label" for="filter-chart">Sélection du graphique</label>';
        $filterChart .= '<select id="filter-chart" class="custom-select">';

        $chartCollections = [
            // 'item-1'                                    => 'Tests PCR',
            // 'spf\charts\positivite'                     => 'C19 | PCR : Taux de positivité',
            // 'closeItem-1'                               => '',

            // 'item-2'                                    => 'Chiffres quotidiens',
            // 'spf\charts\quotidienEntreesHp'             => 'C19 | Quotidien : hospitalisations',
            // 'spf\charts\quotidienEntreesRea'            => 'C19 | Quotidien : soins critiques',
            // 'spf\charts\quotidienDeces'                 => 'C19 | Quotidien : décès',
            // 'spf\charts\quotidienRad'                   => 'C19 | Quotidien : retours à domicile',
            // 'closeItem-2'                               => '',

            'item-3'                                    => 'Taux d\'occupation des hôpitaux',
            'owid\charts\nbOccupationHp'                => 'C19 | Occupation : hospitalisations',
            // 'spf\charts\nbOccupationRea'                => 'C19 | Occupation : soins critiques',
            'closeItem-3'                               => '',

            'item-4'                                    => 'Chiffres cumulés',
            'owid\charts\totalDeathPerMillion'          => 'C19 | Cumulé : décès par millions d\'habitants',
            'owid\charts\totalCasesPerMillion'          => 'C19 | Cumulé : cas par millions d\'habitants',
            // 'spf\charts\nbCumuleDecesAge'               => 'C19 | Cumulé : décès par âge',
            // 'spf\charts\nbCumuleRad'                    => 'C19 | Cumulé : retours à domicile',
            'closeItem-4'                               => '',

            'item-5'                                    => 'Chiffres sur la vaccinations',
            // 'spf\charts\quotidienVaccinationAge'        => 'C19 | Quotidien : vaccinations par âge',
            // 'spf\charts\quotidienVaccinationVaccin'     => 'C19 | Quotidien : vaccinations par vaccin',
            // 'spf\charts\nbCumuleVaccinationAge'         => 'C19 | Cumulé : vaccinations par âge',
            'owid\charts\totalFirstVaccinatedPerHundred'=> 'C19 | Partiellement vaccinés %',
            'owid\charts\totalVaccinatedPerHundred'     => 'C19 | Totalement vaccinés %',
            'closeItem-5'                               => '',
        ];

        foreach ($chartCollections as $key => $text) {

            if (strstr($key, 'item')) {
                $filterChart .= '<optgroup label="' . $text . '">';
            } elseif (strstr($key, 'closeItem')) {
                $filterChart .= '</optgroup>';
            } else {

                $selected = '';
                if ($_SESSION['owid_filterChart'] == $key) {
                    $selected = ' selected="selected"';
                }

                $filterChart .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
            }
        }

        $filterChart .= '</select>';
        $filterChart .= '</div>';

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

        return $filterChart;
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

        $filterCountry  = '<div class="form-group col-lg-3" style="padding-left:0; padding-right:0;">';
        $filterCountry .= '<label class="form-label col-lg-12" for="filter-country">Pays</label>';
        $filterCountry .= '<select id="filter-country" class="selectpicker col-10" data-style="btn-default" multiple="multiple" data-live-search="true">';

        foreach ($countries as $iso => $location) {
            $selected = '';
            if (in_array($iso, $_SESSION['owid_filterCountry'])) {
                $selected = ' selected';
            }

            $filterCountry .= '<option value="' . $iso . '"' . $selected  . '>' . $location . '</option>';
        }

        $filterCountry .= '</select>';
        $filterCountry .= '<button id="country-search" type="submit" class="col-2 btn btn-primary" style="border-radius:0 5px 5px 0; position:relative; left:-15px;">';
        $filterCountry .= '<i class="fas fa-search" style="position:relative; left:-2px;"></i></button>';
        $filterCountry .= '</div>';

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

        return $filterCountry;
    }


    private static function chartFilterInterval()
    {
        $filterInterval  = '<div class="form-group col-lg-3">';
        $filterInterval .= '<label class="form-label" for="filter-interval">Période</label>';
        $filterInterval .= '<select id="filter-interval" class="custom-select">';

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

            $filterInterval .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filterInterval .= '</select>';
        $filterInterval .= '</div>';

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

        return $filterInterval;
    }
}
