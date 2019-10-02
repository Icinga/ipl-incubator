<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class BarGraph extends BaseHtmlElement
{
    protected $tag = 'svg';

    protected $dataSets = [];

    protected $graphData = [];

    protected $amountLines = 4;

    protected $outerMarginLeft = 30;

    protected $outerMarginTop = 15;

    protected $graphHeight = 120;

    protected $totalWidth;

    protected $textMargin = 5;

    protected $barWidth = 20;

    protected $defaultAttributes = ['class' => 'bar-graph'];

    /**
     * BarGraph constructor.
     *
     * @param $title    string  Title that is to be displayed under the graph
     * @param $data     array   Array of values
     * @param $attributes   array
     */
    public function __construct($title, $data, $attributes = null)
    {
        $this->addAttributes($attributes);
        $this->addDataSet($title, $data);

        return $this;
    }

    /**
     * Adds another set of data.
     *
     * @param $title    string  Title that is to be displayed under the data set
     * @param $data     array   Array of values
     *
     * @return $this
     */
    public function addDataSet($title, $data) {
        $this->dataSets[] = ['title' => $title, 'data' => $data];

        return $this;
    }

    /**
     * Draws the graph after all data has been added.
     *
     * @return $this|array
     */
    public function draw()
    {
        if (count($this->dataSets) == 0) return [];
        $this->calcGraphData();

        $this->addAttributes(['viewbox' => '0 0 ' . $this->totalWidth . ' 150']);

        $graph = [$this->drawGrid()];
        foreach ($this->dataSets as $key => $dataSet) {
            $graph[] = $this->drawDataSet($key, $dataSet);
        }
        $this->setContent($graph);

        return $this;
    }

    /**
     * Calculates the information necessary to draw the grid
     *
     * @return array
     */
    protected function calcGraphData()
    {
        $barCounter = 0;
        $this->graphData['max'] = -INF;
        $this->graphData['min'] = INF;
        foreach ($this->dataSets as $set) {
            $this->graphData['max'] = max($this->graphData['max'], max($set['data']));
            $this->graphData['min'] = min($this->graphData['min'], min($set['data']));
            $barCounter += count($set['data']);
        }

        $this->graphData['dif'] = $this->graphData['max'] - $this->graphData['min'];
        $this->graphData['sta'] = $this->calculateStart($this->graphData);
        $this->graphData['jmp'] = $this->calculateJumps($this->graphData);

        $this->totalWidth = ($barCounter * $this->barWidth) + 100;

        return $this->graphData;
    }

    protected function drawGrid()
    {
        $graphData = $this->graphData;

        $graphMarginTop = $this->graphHeight + $this->outerMarginTop;

        $lines = [];
        for ($i = 1; $i <= $this->amountLines; $i++) {

            $label = $i * $graphData['jmp'] + $graphData['sta'];

            $height = $this->outerMarginTop + ($this->graphHeight - $this->getRelativeValue(
                $i * $graphData['jmp'],
                $this->amountLines * $graphData['jmp'],
                $this->graphHeight
            ));

            $lines[] = [
                Html::tag('g', ['class' => 'line'], [
                    Html::tag(
                        'text',
                        [
                            'x' => $this->outerMarginLeft,
                            'y' => $height + 4,
                            'fill' => 'grey',
                            'text-anchor' => 'end'
                        ],
                        $label),
                    Html::tag(
                        'path',
                        [
                            'd' => sprintf(
                                'M%s,%s L%s,%s',
                                $this->textMargin + $this->outerMarginLeft,
                                $height,
                                $this->totalWidth,
                                $height),
                            'stroke' => 'lightgray'
                        ]
                    )

                ])
            ];
        }

        $lines[] = [
            Html::tag('g', ['class' => 'bottom-line'], [
                Html::tag(
                    'text',
                    [
                        'x' => $this->outerMarginLeft,
                        'y' => $graphMarginTop + 4,
                        'fill' => 'grey',
                        'text-anchor' => 'end'
                    ],
                    $graphData['sta']
                ),
                Html::tag(
                    'path',
                    [
                        'd' => sprintf(
                            'M%s,%s L%s,%s',
                            $this->textMargin + $this->outerMarginLeft,
                            $graphMarginTop,
                            $this->totalWidth,
                            $graphMarginTop),
                        'stroke' => 'gray'
                    ]
                )
            ])
        ];

        return Html::tag('g', ['class' => 'bar-grid'], $lines);
    }

    protected function drawDataSet($pos, $dataSet)
    {
        $width = ($this->totalWidth - $this->outerMarginLeft) / count($this->dataSets);

        return Html::tag(
            'g',
            ['class' => 'data-set', 'transform' => 'translate(' . ($width * $pos) . ',0)'],
            [
                $this->drawBars($dataSet['data']),
                Html::tag(
                    'text',
                    [
                        'fill' => 'gray',
                        'text-anchor' => 'middle',
                        'transform' => sprintf(
                            'translate(%s, %s)',
                            ($width + $this->textMargin) / 2 + $this->outerMarginLeft,
                            $this->graphHeight + $this->outerMarginTop + $this->textMargin + 10
                        )
                    ],
                    Html::tag('tspan', [], $dataSet['title'])
                )
            ]);
    }

    protected function drawBars($data) {
        $bars = [];
        foreach ($data as $order => $datum) {
            $graphData = $this->graphData;
            $height = $this->getRelativeValue(
                $datum - $graphData['sta'],
                $this->amountLines * $graphData['jmp'],
                $this->graphHeight
            );

            $graphText = Html::tag(
                'text',
                [
                    'text-anchor' => 'middle',
                    'x' => $this->barWidth / 2,
                    'y' => '-2', 'fill' => 'grey'
                ],
                $datum
            );

            $barLeftMargin = $this->outerMarginLeft
                + $this->textMargin
                + ($this->barWidth * $order)
                + ($order + 1) * $this->getBarMargins($data);

            $bars[] = Html::tag(
                'g',
                [
                    'transform' => sprintf(
                        'translate(%s, %s)',
                        $barLeftMargin,
                        $this->graphHeight + $this->outerMarginTop - $height
                    )
                ],
                [
                    Html::tag('path', [
                        'd' => $this->getPathString($height, $this->barWidth),
                        'class' => 'bar-' . $order,
                        'fill' => sprintf('#%06X', mt_rand(0, 0xFFFFFF))
                    ]),
                    $graphText
                ]);
        }

        return $bars;
    }

    protected function getBarMargins($data) {
        $graphWidth = ($this->totalWidth - $this->outerMarginLeft) / count($this->dataSets) - $this->textMargin;
        $spaceTakenByBars = (count($data) * $this->barWidth);
        return ($graphWidth - $spaceTakenByBars) / (count($data) + 1);
    }

    protected function getPathString($height, $width)
    {
        $path = sprintf(
            "M3,0 L%s,0 C%s.6568542,-3.04359188e-16 %s,1.34314575 %s,3 L%s,%s L%s,%s L0,%s L0,3"
            . " C-2.02906125e-16,1.34314575 1.34314575,3.04359188e-16 3,0 Z",
            $width - 3,
            $width - 2,
            $width,
            $width,
            $width,
            $height,
            $width,
            $height,
            $height
        );

        return $path;
    }

    protected function getRelativeValue($value, $relativeMax, $absoluteMax)
    {
        return ($value / $relativeMax * 100) * $absoluteMax / 100;
    }

    protected function calculateStart($graphData)
    {
        $start = 0;
        if ($graphData['dif'] < $graphData['min']) {
            $start = $graphData['min'] - $graphData['min'] / 10;
        }

        return $this->roundFitting($start);
    }

    protected function calculateJumps($graphData)
    {
        $jump = ($graphData['max'] - $graphData['sta']) / $this->amountLines;

        while ($this->roundFitting($jump) < (($graphData['max'] - $graphData['sta']) / $this->amountLines)) {
            $jump = $jump * 1.1;
        }

        return $this->roundFitting($jump);
    }

    protected function roundFitting($value)
    {
        if ($value > 0.1 && $value <= 1) {
            $value = round($value / 0.05) * 0.05;
        } elseif ($value > 1 && $value <= 5) {
            $value = round($value / 0.5) * 0.5;
        } elseif ($value > 5 && $value <= 10) {
            $value = round($value / 2.5) * 2.5;
        } elseif ($value > 10) {
            $value = round($value / 5) * 5;
        }

        return $value;
    }
}
