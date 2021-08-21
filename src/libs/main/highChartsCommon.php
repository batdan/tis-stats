<?php
namespace main;

class highChartsCommon
{
    public static function exportImgLogo($yAxisleft = false)
    {
        $imgSrc     = 'https://stats.lachainehumaine.com/img/logo_complet_2_30pct_nb.png';

        $imgWidth   = 320;
        $imgHeight  = 85;

        $x  = 20;
        $y  = 95;

        if ($yAxisleft) {
            $x  = 100;
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
}