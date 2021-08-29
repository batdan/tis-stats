<?php
namespace owid\maps;

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

    <body style="margin:0;">
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
        $filterChart  = '<div class="form-group col-lg-3">';
        $filterChart .= '<label class="form-label" for="filter-map">Sélection de la carte C19</label>';
        $filterChart .= '<select id="filter-map" class="form-select">';

        $chartCollections = [
            'item-1'                                        => 'Tests PCR',
            'owid\maps\pcrPositivite'                       => 'PCR : taux de positivité',
            // 'owid\maps\pcrCumulTests'                    => 'PCR : nb de tests réalisés',
            'closeItem-1'                                   => '',

            // 'item-2'                                    => 'Chiffres quotidiens',
            // 'owid\maps\quotidienEntreesHp'             => 'Quotidien : hospitalisations',
            // 'owid\maps\quotidienEntreesRea'            => 'Quotidien : soins critiques',
            // 'owid\maps\quotidienDeces'                 => 'Quotidien : décès',
            // 'owid\maps\quotidienRad'                   => 'Quotidien : retours à domicile',
            // 'closeItem-2'                               => '',
            
            'item-3'                                        => 'Occupation des hôpitaux',
            'owid\maps\nbOccupationHp'                      => 'Occupation : hospitalisations',
            'owid\maps\nbOccupationRea'                     => 'Occupation : soins critiques',
            'closeItem-3'                                   => '',

            'item-4'                                        => 'Chiffres cumulés',
            'owid\maps\deathsPerMillion'                    => 'Cumulé : décès par million',
            // 'owid\maps\nbCumuleDecesAge'                 => 'Cumulé : décès par âge',
            // 'owid\maps\nbCumuleRad'                      => 'Cumulé : retours à domicile',
            'closeItem-4'                                   => '',

            // 'item-5'                                    => 'Chiffres sur la vaccinations',
            // 'owid\maps\quotidienVaccinationAge'        => 'Quotidien : vaccinations par âge',
            // 'owid\maps\quotidienVaccinationVaccin'     => 'Quotidien : vaccinations par vaccin',
            // 'owid\maps\nbCumuleVaccinationAge'         => 'Cumulé : vaccinations par âge',
            // 'owid\maps\nbCumuleVaccinationVaccin'      => 'Cumulé : vaccinations par vaccin',
            // 'closeItem-5'                               => '',
        ];

        foreach ($chartCollections as $key => $text) {

            if (strstr($key, 'item')) {
                $filterChart .= '<optgroup label="' . $text . '">';
            } elseif (strstr($key, 'closeItem')) {
                $filterChart .= '</optgroup>';
            } else {

                $selected = '';
                if ($_SESSION['owid_filterMap'] == $key) {
                    $selected = ' selected="selected"';
                }

                $filterChart .= '<option value="' . $key . '"' . $selected  . '>' . $text . '</option>';
            }
        }

        $filterChart .= '</select>';
        $filterChart .= '</div>';

        $filterChart .= <<<eof
        <script type="text/javascript">
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
        </script>
eof;

        return $filterChart;
    }
}
