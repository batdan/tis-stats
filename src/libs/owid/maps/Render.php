<?php

namespace owid\maps;

use tools\dbSingleton;

/**
 * Accès à la collection de cartes disponibles pour highcharts
 * https://code.highcharts.com/mapdata/
 */
class Render
{
    private static $dbh;

    private static $jsrender = '';


    public static function html($chartName, $title, $js, $backLink = true, $filterActiv = [])
    {
        self::$dbh = dbSingleton::getInstance();

        $backLinkLCH = self::backLink($backLink);
        $chartFilter = self::chartFilters($filterActiv);

        $jsrender = self::$jsrender;
        $md5Css = md5_file(__DIR__ . '/../../../css/styles.css');

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
            .maps {
                height: 600px;
                width: 100%;
                max-width: 1200px;
                margin: 0 auto;
            }

            .highcharts-tooltip>span {
                padding: 10px;
                white-space: normal !important;
                width: 200px;
            }

            .loading {
                margin-top: 10em;
                text-align: center;
                color: gray;
            }

            .f32 .flag {
                vertical-align: middle !important;
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
        <script src="https://code.highcharts.com/mapdata/custom/world.js"></script>

        <script type="text/javascript">
            $js
            $jsrender

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
        ];

        $filterActiv = array_merge($filterActivDefault, $filterActiv);

        $chartFilters = '<div class="row" style="margin-bottom:10px;">';
        $chartFilters .= self::chartSelect();
        $chartFilters .= '</div>';

        return $chartFilters;
    }


    private static function chartSelect()
    {
        $filter  = '<div class="form-group col-lg-4">';
        $filter .= '<label class="form-label" for="filter-map">Sélection de la carte C19</label>';
        $filter .= '<select id="filter-map" class="form-select">';

        $chartCollections = [
            'item-1'                                        => 'Chiffres cumulés',
            'owid\maps\DeathsPerMillion'                    => 'Cumulé : décès par million',
            'closeItem-1'                                   => '',
            
            'item-2'                                        => 'Tests PCR',
            'owid\maps\PcrPositivite'                       => 'PCR : taux de positivité',
            'closeItem-2'                                   => '',

            'item-3'                                        => 'Occupation des hôpitaux',
            'owid\maps\NbOccupationHp'                      => 'Occupation : hospitalisations',
            'owid\maps\NbOccupationRea'                     => 'Occupation : soins critiques',
            'closeItem-3'                                   => '',

            'item-4'                                        => 'Chiffres sur la vaccinations',
            'owid\maps\TotalFirstVaccinatedPerHundred'      => 'Cumulé : vaccinations 1 dose',
            'owid\maps\TotalVaccinatedPerHundred'           => 'Cumulé : vaccinations complètes',
            'closeItem-4'                                   => '',
        ];

        foreach ($chartCollections as $key => $text) {
            if (strstr($key, 'item')) {
                $filter .= '<optgroup label="' . $text . '">';
            } elseif (strstr($key, 'closeItem')) {
                $filter .= '</optgroup>';
            } else {
                $selected = '';
                if ($_SESSION['owid_filterMap'] == $key) {
                    $selected = ' selected="selected"';
                }

                $filter .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
            }
        }

        $filter .= '</select>';
        $filter .= '</div>';

        self::$jsrender .= <<<eof
            $("#filter-map").change( function() {
                $.post("/ajax/owid/filterMap.php",
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
}
