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
                sourceWidth: 1200,
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
    public static function creditLCH()
    {
        $jsEvent = <<<eof

        credits: {
            text: 'www.lachainehumaine.com',
            href: '',
            position: {
                align: 'center',
                verticalAlign: 'top',
                x: 0,
                y: 95
            },
            style: {
                color: '#ccc',
                fontSize: '22px',
            }
        },
eof;

        return $jsEvent;
    }


    public static function chartText($text)
    {
        return str_replace("'", '&#39;', $text);
    }
}
