<?php
namespace main;

class highChartsCommon
{
    /**
     * Permet de gérer la dimension des exports de graphiques
     *
     * @param   boolean     $yAxisleft      Paramètre inutile mais conservé pour la rétrocompatibilité
     * @return  string
     */
    public static function exportImgLogo($yAxisleft = false)
    {
        $jsEvent = <<<eof
            exporting: {
                sourceWidth: 1500,
                sourceHeight: 600,
                scale: 1
            },
eof;

        return $jsEvent;
    }


    /**
     * Code permettant d'afficher une image dans les exports de graphique
     * Méthode abandonnée au profit de l'affichage d'un credit avec le nom de domaine
     *
     * @param   boolean     $yAxisleft      gestion de l'espace à gauche pour les graphiques avec ordonnée à gauche
     * @return  string
     */
    public static function exportImgLogoOld($yAxisleft = false)
    {
        $imgSrc     = 'https://stats.lachainehumaine.com/img/logo_complet_2_30pct_nb.png';

        $imgWidth   = 274;
        $imgHeight  = 73;

        $x  = 10;
        $y  = 70;

        if ($yAxisleft) {
            $x  = 110;
        }

        $jsEvent = <<<eof
            chart: {
                events: {
                    load: function () {
                        if (this.options.chart.forExport) {
                            this.renderer.image('$imgSrc',
                                $x,         // x
                                $y,         // y
                                $imgWidth,  // width
                                $imgHeight  // height
                            ).add();
                        }
                    },
                    beforePrint: function() {
                        x=this.renderer.image('$imgSrc',
                            $x,         // x
                            $y,         // y
                            $imgWidth,  // width
                            $imgHeight  // height
                        ).add();
                        this.print();
                    },
                    afterPrint: function() {
                        x.element.remove();
                    }
                }
            },
            exporting: {
                chartOptions: {
                    chart: {
                        events: {
                            load: function() {
                                this.renderer.image('$imgSrc',
                                    $x,         // x
                                    $y,         // y
                                    $imgWidth,  // width
                                    $imgHeight  // height
                                ).add();
                            }
                        }
                    }
                },
                sourceWidth: 1200,
                sourceHeight: 600,
                scale: 1
            },
eof;

        return $jsEvent;
    }


    /**
     * Utilisation du crédit pour afficher le domaine LCH
     * @return string
     */
    public static function creditLCH($x=0, $y=95)
    {
        $jsEvent = <<<eof

        credits: {
            text: 'www.lachainehumaine.com',
            href: '',
            position: {
                align: 'center',
                verticalAlign: 'top',
                x: $x,
                y: $y
            },
            style: {
                color: '#ccc',
                fontSize: '18px',
            }
        },
eof;

        return $jsEvent;
    }


    /**
     * Utilisation du crédit pour afficher le domaine LCH
     * @return string
     */
    public static function creditMapsLCH()
    {
        return <<<eof
            credits: {
                text: 'www.lachainehumaine.com',
                href: 'https://www.lachainehumaine.com',
                position: {
                    align: 'right',
                    verticalAlign: 'bottom',
                    x: 0,
                    y: -44
                },
                style: {
                    color: '#0F9900',
                    fontSize: '16px',
                }
            },
eof;
    }


    /**
     * Abcisse de dates
     * @param    array   $jours      Liste des jours
     * @return   string
     */
    public static function xAxis($jours)
    {
        return <<<eof
            xAxis: {
                categories: [$jours],
                type: 'datetime',
                dateTimeLabelFormats: {
                    week: '%e of %b'
                },
                labels: {
                    format: '{value:%Y-%m-%d}',
                    rotation: -45,
                    style: {
                        fontSize: 12
                    }
                },
                tickWidth: 1,
                tickLength: 7,
                tickInterval: 2
            },
eof;
    }


    /**
     * Abcisse libre
     * @param    array   $xAxys      Information de l'axe des abcisse
     * @return   string
     */
    public static function xAxisYears($xAxys)
    {
        return <<<eof
            xAxis: {
                categories: [$xAxys],
                labels: {
                    rotation: -45,
                    style: {
                        fontSize: 14
                    }
                },
                tickWidth: 1,
                tickLength: 7,
                tickInterval: 1
            },
eof;
    }


    public static function legend($where = 'right')
    {
        if ($where == 'right') {
            $legend = <<<eof
                legend: {
                    layout: 'vertical',
                    align: 'right',
                    verticalAlign: 'middle'
                },
eof;
        }

        if ($where == 'bottom') {
            $legend = <<<eof
                legend: {
                    layout: 'vertical',
                    align: 'bottom',
                    verticalAlign: 'middle'
                },
eof;
        }
    }


    public static function responsive()
    {
        return <<<eof
            responsive: {
                rules: [{
                    condition: {
                        maxWidth: 1900
                    },
                    chartOptions: {
                        legend: {
                            layout: 'horizontal',
                            align: 'center',
                            verticalAlign: 'bottom'
                        }
                    }
                }]
            }
eof;
    }


    public static function colorCrountryFra()
    {
        return <<<eof
        color: '#000',
        dashStyle: 'ShortDash',
eof;
    }


    public static function chartText($text)
    {
        return str_replace("'", '&#39;', $text);
    }
}
