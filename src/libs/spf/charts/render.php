<?php
namespace spf\charts;

use tools\dbSingleton;

class render
{
    private static $dbh;


    public static function html($chartName, $title, $js, $backLink = true, $filterActiv)
    {
        self::$dbh = dbSingleton::getInstance();

        $backLinkLCH = self::backLink($backLink);
        $chartFilter = self::chartFilters($filterActiv);

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
            'region'    => false,
            'interval'  => false,
            'age'       => false,
            'age2'      => false,
            'vaccin'    => false,
        ];

        $filterActiv = array_merge($filterActivDefault, $filterActiv);

        $chartFilters = '<div class="row" style="margin-bottom:10px;">';

        $chartFilters .= self::chartSelect();

        if ($filterActiv['region']) {
            $chartFilters .= self::chartFilterRegion();
        }

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

        $chartFilters .= '</div>';

        return $chartFilters;
    }


    private static function chartSelect()
    {
        $filterRegion  = '<div class="form-group col-lg-3">';
        $filterRegion .= '<label class="form-label" for="filter-chart">Sélection du graphique</label>';
        $filterRegion .= '<select id="filter-chart" class="form-select">';

        $chartCollections = [
            'spf\charts\positivite'                     => 'C19 | PCR : Taux de positivité',
            'spf\charts\quotidienEntreesHp'             => 'C19 | Quotidien : hospitalisations',
            'spf\charts\quotidienEntreesRea'            => 'C19 | Quotidien : soins critiques',
            'spf\charts\quotidienDeces'                 => 'C19 | Quotidien : décès',
            'spf\charts\quotidienRad'                   => 'C19 | Quotidien : retours à domicile',
            'spf\charts\quotidienVaccinationAge'        => 'C19 | Quotidien : Vaccinations par âge',
            'spf\charts\quotidienVaccinationVaccin'     => 'C19 | Quotidien : Vaccinations par vaccin',
            'spf\charts\nbCumuleHp'                     => 'C19 | Cumulé : hospitalisations',
            'spf\charts\nbCumuleRea'                    => 'C19 | Cumulé : soins critiques',
            'spf\charts\nbCumuleDeces'                  => 'C19 | Cumulé : décès',
            'spf\charts\nbCumuleDecesAge'               => 'C19 | Cumulé : décès par âge',
            'spf\charts\nbCumuleRad'                    => 'C19 | Cumulé : retours à domicile',
            'spf\charts\nbCumuleVaccinationAge'         => 'C19 | Cumulé : Vaccinations par âge',
            'spf\charts\nbCumuleVaccinationVaccin'      => 'C19 | Cumulé : Vaccinations par vaccin',
        ];

        foreach ($chartCollections as $chart => $text) {
            $selected = '';
            if ($_SESSION['filterChart'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filterRegion .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filterRegion .= '</select>';
        $filterRegion .= '</div>';

        $filterRegion .= <<<eof
        <script type="text/javascript">
            $("#filter-chart").change( function() {
                $.post("/ajax/filterChart.php",
                {
                    filterChart : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
        </script>
eof;

        return $filterRegion;
    }


    private static function chartFilterRegion()
    {
        $filterRegion  = '<div class="form-group col-lg-3">';
        $filterRegion .= '<label class="form-label" for="filter-region">Région</label>';
        $filterRegion .= '<select id="filter-region" class="form-select">';

        $req = "SELECT region, nccenr FROM geo_reg2018 ORDER BY id";
        $sql = self::$dbh->query($req);

        $selected = '';
        if ($_SESSION['filterRegionId'] == 0) {
            $selected = ' selected="selected"';
        }

        $filterRegion .= '<option value="0"' . $selected  . '>FRANCE</option>';

        while ($res = $sql->fetch()) {
            $selected = '';
            if ($_SESSION['filterRegionId'] == $res->region) {
                $selected = ' selected="selected"';
            }

            $filterRegion .= '<option value="' . $res->region . '"' . $selected  . '>' . $res->nccenr . '</option>';
        }

        $filterRegion .= '</select>';
        $filterRegion .= '</div>';

        $filterRegion .= <<<eof
        <script type="text/javascript">
            $("#filter-region").change( function() {
                $.post("/ajax/filterRegion.php",
                {
                    filterRegion : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
        </script>
eof;

        return $filterRegion;
    }


    private static function chartFilterInterval()
    {
        $filterRegion  = '<div class="form-group col-lg-3">';
        $filterRegion .= '<label class="form-label" for="filter-interval">Période</label>';
        $filterRegion .= '<select id="filter-interval" class="form-select">';

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
            if ($_SESSION['filterInterval'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filterRegion .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filterRegion .= '</select>';
        $filterRegion .= '</div>';

        $filterRegion .= <<<eof
        <script type="text/javascript">
            $("#filter-interval").change( function() {
                $.post("/ajax/filterInterval.php",
                {
                    filterInterval : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
        </script>
eof;

        return $filterRegion;
    }


    private static function chartFilterAge()
    {
        $filterRegion  = '<div class="form-group col-lg-3">';
        $filterRegion .= '<label class="form-label" for="filter-age">Age</label>';
        $filterRegion .= '<select id="filter-age" class="form-select">';

        $chartInterval = [
            '0'     => 'Tous les ages',
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
            if ($_SESSION['filterAge'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filterRegion .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filterRegion .= '</select>';
        $filterRegion .= '</div>';

        $filterRegion .= <<<eof
        <script type="text/javascript">
            $("#filter-age").change( function() {
                $.post("/ajax/filterAge.php",
                {
                    filterAge : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
        </script>
eof;

        return $filterRegion;
    }


    private static function chartFilterAge2()
    {
        $filterRegion  = '<div class="form-group col-lg-3">';
        $filterRegion .= '<label class="form-label" for="filter-age2">Age</label>';
        $filterRegion .= '<select id="filter-age2" class="form-select">';

        $chartInterval = [
            '0'  => 'Tous les ages',
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
            if ($_SESSION['filterAge2'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filterRegion .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filterRegion .= '</select>';
        $filterRegion .= '</div>';

        $filterRegion .= <<<eof
        <script type="text/javascript">
            $("#filter-age2").change( function() {
                $.post("/ajax/filterAge2.php",
                {
                    filterAge2 : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
        </script>
eof;

        return $filterRegion;
    }


    private static function chartFilterVaccin()
    {
        $filterRegion  = '<div class="form-group col-lg-3">';
        $filterRegion .= '<label class="form-label" for="filter-vaccin">Vaccin</label>';
        $filterRegion .= '<select id="filter-vaccin" class="form-select">';

        $chartInterval = [
            0 => 'Tous Vaccins',
            1 => 'Pfizer',
            2 => 'Moderna',
            3 => 'AstraZeneka',
            4 => 'Janssen',
        ];

        foreach ($chartInterval as $chart => $text) {
            $selected = '';
            if ($_SESSION['filterVaccin'] == $chart) {
                $selected = ' selected="selected"';
            }

            $filterRegion .= '<option value="' . $chart . '"' . $selected  . '>' . $text . '</option>';
        }

        $filterRegion .= '</select>';
        $filterRegion .= '</div>';

        $filterRegion .= <<<eof
        <script type="text/javascript">
            $("#filter-vaccin").change( function() {
                $.post("/ajax/filterVaccin.php",
                {
                    filterVaccin : $(this).find(":selected").val()
                },
                function success(data)
                {
                    console.log(data);
                    history.go(0);
                }, 'json');
            });
        </script>
eof;

        return $filterRegion;
    }
}
