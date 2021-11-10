<?php

namespace timeline;

use tools\dbSingleton;

class Timeline
{
    private $dbh;

    private $timelineId;
    private $timelineName;
    private $timelineExist;

    private $title;
    private $subTitle;

    private $dataSeries;
    private $highChartsJs;


    /**
     * @param boolean $cache    Activation ou non du cache des résultats de requêtes
     */
    public function __construct(bool $cache = true)
    {
        $this->dbh = dbSingleton::getInstance();

        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $this->timelineId = $_GET['id'];
            $this->timelineName = 'timeline_' . $this->timelineId;

            $this->infosTimeline();
        }

        if ($this->timelineExist) {
            $this->getData();

            $this->getData();
            $this->highChartsJs();
        }
    }


    private function infosTimeline()
    {
        $req = "SELECT title, sub_title FROM timeline WHERE id = :id";
        $sql = $this->dbh->prepare($req);
        $sql->execute([':id' => $this->timelineId]);


        if ($sql->rowCount()) {
            $this->timelineExist = true;

            $res = $sql->fetch();

            $this->title    = $res->title;
            $this->subTitle = $res->sub_title;
        }
    }


    /**
     * Récupération des données de la statistique
     * en cache ou en BDD
     */
    public function getData()
    {
        $req = "SELECT      DATE_FORMAT(date_item, '%Y') AS  Y,
                            DATE_FORMAT(date_item, '%m') AS  m,
                            DATE_FORMAT(date_item, '%d') AS  d,
                            title, description, link, link_target

                FROM        timeline_items

                WHERE       id_timeline = :id_timeline";

        $sql = $this->dbh->prepare($req);
        $sql->execute([':id_timeline' => $this->timelineId]);

        $this->dataSeries = [];

        while ($res = $sql->fetch()) {
            $link   = '';

            if (!empty($res->link)) {
                $target = empty($res->link_target) ? '_blank' : $res->link_target;

                $link = <<<eof
                events: {
                    click: function () {
                        window.open('$res->link', '$target')
                    },
                    mouseOver: function (e) {
                        var elem = e.target.dataLabel.element
                        elem.setAttribute('style', 'cursor:pointer')
                    }
                },
eof;
            }

            $this->dataSeries[] = <<<eof
            {
                x: Date.UTC({$res->Y}, {$res->m}, {$res->d}),
                name: '{$res->title}',
                label: '{$res->title}',
                $link
                description: '{$res->description}'
            }
eof;
        }

        $this->dataSeries = implode(',', $this->dataSeries);
    }


    /**
     * Script de configuration de graphique Highcharts
     */
    private function highChartsJs()
    {
        $this->highChartsJs = <<<eof
        Highcharts.chart('{$this->timelineName}', {
            credits: {
                enabled: false
            },
            chart: {
                zoomType: 'x',
                type: 'timeline'
            },
            xAxis: {
                type: 'datetime',
                visible: false
            },
            yAxis: {
                gridLineWidth: 1,
                title: null,
                labels: {
                    enabled: false
                }
            },
            legend: {
                enabled: false
            },
            title: {
                text: '$this->title'
            },
            subtitle: {
                text: '$this->subTitle'
            },

            tooltip: {
                style: {
                    width: 300
                }
            },

            series: [{
                dataLabels: {
                    allowOverlap: false,
                    format: '<span style="color:{point.color}">● </span><span style="font-weight: bold;" > ' +
                    '{point.x:%d %b %Y}</span><br/>{point.label}'
                },

                marker: {
                    symbol: 'circle'
                },

                data: [{$this->dataSeries}]
            }]
        });
        eof;
    }


    /**
     * Redu HTML
     */
    public function render()
    {
        $backLink = (isset($_GET['internal'])) ? false : true;

        $filterActiv = [];

        echo Render::html(
            $this->timelineName,
            $this->title,
            $this->highChartsJs,
            $backLink
        );
    }
}
