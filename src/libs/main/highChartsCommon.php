<?php
namespace main;

class highChartsCommon
{
    public static function exportImgLogo($yAxisleft = false)
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


    public static function imgLogo()
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
                color: '#dddddd',
                fontSize: '22px',
                // backgroundImage: 'url($imgSrc)'
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
