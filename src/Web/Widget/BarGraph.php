<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

class BarGraph extends BaseHtmlElement
{
    protected $tag = 'svg';

    protected $dataSets = [];

    protected $graphData = [];

    protected $amountLines = 4;

    protected $outerMarginLeft = 30;

    protected $outerMarginTop = 15;

    protected $graphHeight = 115;

    protected $totalWidth;

    protected $textMargin = 5;

    protected $barWidth = 20;

    protected $defaultAttributes = ['class' => 'bar-graph'];

    /**
     * BarGraph constructor.
     *
     * @param string        $title         Title that is to be displayed under the graph
     * @param array         $data          Array of values
     * @param Attributes    $attributes    HTML attributes
     */
    public function __construct($title, array $data, Attributes $attributes = null)
    {
        $this->addAttributes($attributes);
        $this->addDataSet($title, $data);
    }

    /**
     * Adds another set of data.
     *
     * @param string  $title    Title that is to be displayed under the data set
     * @param array   $data     Array of values
     *
     * @return $this
     */
    public function addDataSet($title, array $data)
    {
        $this->dataSets[] = ['title' => $title, 'data' => $data];

        return $this;
    }

    /**
     * Draws the graph after all data has been added.
     *
     * @return $this|array   Generated graph ready to be rendered
     */
    public function draw()
    {
        if (count($this->dataSets) === 0) {
            return [];
        }

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

        $this->graphData['difference'] = $this->graphData['max'] - $this->graphData['min'];
        $this->graphData['start'] = $this->calculateStart($this->graphData);
        $this->graphData['jump'] = $this->calculateJumps($this->graphData);

        $this->totalWidth = $barCounter * $this->getBarWidth() + 100;

        return $this->graphData;
    }

    /**
     * @return HtmlElement
     */
    protected function drawGrid()
    {
        $graphMarginTop = $this->graphHeight + $this->outerMarginTop;

        $lines = [];
        for ($i = 1; $i <= $this->getAmountLines(); $i++) {

            $label = $i * $this->graphData['jump'] + $this->graphData['start'];

            $height = $this->outerMarginTop + ($this->graphHeight - $this->getRelativeValue(
                $i * $this->graphData['jump'],
                $this->getAmountLines() * $this->graphData['jump'],
                $this->graphHeight
            ));

            $lines[] = new HtmlElement(
                'g',
                new Attributes(['class' => 'line']),
                [
                    new HtmlElement(
                        'text',
                        new Attributes([
                            'class'         => 'svg-text',
                            'x'             => $this->outerMarginLeft,
                            'y'             => $height + 4,
                            'fill'          => 'grey',
                            'text-anchor'   => 'end'
                        ]),
                        $label
                    ),
                    new HtmlElement(
                        'path',
                        new Attributes([
                            'd' => sprintf(
                                'M%s,%s L%s,%s',
                                $this->textMargin + $this->outerMarginLeft,
                                $height,
                                $this->totalWidth,
                                $height),
                            'stroke' => 'lightgray'
                        ])
                    )
                ]
            );
        }

        $lines[] = new HtmlElement(
            'g',
            new Attributes(['class' => 'bottom-line']),
            [
                new HtmlElement(
                    'text',
                    new Attributes([
                        'class'         => 'svg-text',
                        'x'             => $this->outerMarginLeft,
                        'y'             => $graphMarginTop + 4,
                        'fill'          => 'grey',
                        'text-anchor'   => 'end'
                    ]),
                    $this->graphData['start']
                ),
                new HtmlElement(
                    'path',
                    new Attributes([
                        'd' => sprintf(
                            'M%s,%s L%s,%s',
                            $this->textMargin + $this->outerMarginLeft,
                            $graphMarginTop,
                            $this->totalWidth,
                            $graphMarginTop),
                        'stroke' => 'gray'
                    ])
                )
            ]
        );

        return new HtmlElement('g', new Attributes(['class' => 'bar-grid']), $lines);
    }

    /**
     * @param   int     $pos
     * @param   array   $dataSet
     *
     * @return HtmlElement
     */
    protected function drawDataSet($pos, $dataSet)
    {
        $width = ($this->totalWidth - $this->outerMarginLeft) / count($this->dataSets);

        return new HtmlElement(
            'g',
            new Attributes([
                'class' => 'data-set',
                'transform' => 'translate(' . ($width * $pos) . ',0)'
                ]),
            [
                $this->drawBars($dataSet['data']),
                new HtmlElement(
                    'text',
                    new Attributes([
                        'class'         => 'svg-text',
                        'fill'          => 'gray',
                        'text-anchor'   => 'middle',
                        'transform'     => sprintf(
                            'translate(%s, %s)',
                            ($width + $this->textMargin) / 2 + $this->outerMarginLeft,
                            $this->graphHeight + $this->outerMarginTop + $this->textMargin + 10
                        )
                    ]),
                    new HtmlElement(
                        'tspan',
                        [],
                        $dataSet['title']
                    )
                )
            ]
        );
    }

    /**
     * @param   array   $data
     *
     * @return  array   $bars   All bars for the data set
     */
    protected function drawBars($data)
    {
        $bars = [];
        foreach ($data as $order => $datum) {
            $graphData = $this->graphData;
            $height = $this->getRelativeValue(
                $datum - $graphData['start'],
                $this->getAmountLines() * $graphData['jump'],
                $this->graphHeight
            );

            //todo: think of something for values < 2 (border radius > height looks buggy)

            $graphText = new HtmlElement(
                'text',
                new Attributes([
                    'class'         => 'svg-text',
                    'text-anchor'   => 'middle',
                    'x'             => $this->getBarWidth() / 2,
                    'y'             => '-2',
                    'fill'          => 'grey'
                ]),
                $datum
            );

            $barLeftMargin = $this->outerMarginLeft
                + $this->textMargin
                + ($this->getBarWidth() * $order)
                + ($order + 1) * $this->getBarMargins($data);

            $bars[] =new HtmlElement(
                'g',
                new Attributes([
                    'transform' => sprintf(
                        'translate(%s, %s)',
                        $barLeftMargin,
                        $this->graphHeight + $this->outerMarginTop - $height
                    )
                ]),
                [
                    new HtmlElement('path',
                        new Attributes([
                        'd' => $this->getPathString($height, $this->getBarWidth()),
                        'class' => 'bar-' . $order,
                        'fill' => sprintf('#%06X', mt_rand(0, 0xFFFFFF))
                        ])
                    ),
                    $graphText
                ]
            );
        }

        return $bars;
    }

    /**
     * @param   array       $data
     *
     * @return  float|int
     */
    protected function getBarMargins($data)
    {
        $graphWidth = ($this->totalWidth - $this->outerMarginLeft) / count($this->dataSets) - $this->textMargin;
        $spaceTakenByBars = (count($data) * $this->getBarWidth());

        return ($graphWidth - $spaceTakenByBars) / (count($data) + 1);
    }

    /**
     * @param   float|int $height
     * @param   float|int $width
     *
     * @return  string
     */
    protected function getPathString($height, $width)
    {
        $path = sprintf(
            'M3,0 L%1$s,0 C%2$s.6568542,-3.04359188e-16 %3$s,1.34314575 %3$s,3 L%3$s,%4$s L%3$s,%4$s L0,%4$s L0,3',
            $width - 3,
            $width - 2,
            $width,
            $height
        );

        return $path . ' C-2.02906125e-16,1.34314575 1.34314575,3.04359188e-16 3,0 Z';
    }

    /**
     * @param     float|int     $value
     * @param     float|int     $relativeMax
     * @param     float|int     $absoluteMax
     *
     * @return    float|int
     */
    protected function getRelativeValue($value, $relativeMax, $absoluteMax)
    {
        return ($value / $relativeMax * 100) * $absoluteMax / 100;
    }

    /**
     * @param   array       $graphData
     *
     * @return  float|int
     */
    protected function calculateStart($graphData)
    {
        $start = 0;
        if ($graphData['difference'] < $graphData['min']) {
            $start = $graphData['min'] - $graphData['min'] / 10;
        }

        return $this->roundFitting($start);
    }

    /**
     * @param   array      $graphData
     *
     * @return  float|int
     */
    protected function calculateJumps($graphData)
    {
        $jump = ($graphData['max'] - $graphData['start']) / $this->getAmountLines();

        while ($this->roundFitting($jump) < (($graphData['max'] - $graphData['start']) / $this->getAmountLines())) {
            $jump = $jump * 1.1;
        }

        return $this->roundFitting($jump);
    }

    /**
     * @param  float|int     $value
     *
     * @return float|int
     */
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

    /**
     * Get this $amountLines
     *
     * @return int
     */
    public function getAmountLines()
    {
        return $this->amountLines;
    }

    /**
     * Set the amount of lines used for the grid
     *
     * @param  int     $amountLines
     *
     * @return $this
     */
    public function setAmountLines($amountLines)
    {
        $this->amountLines = $amountLines;
        return $this;
    }

    /**
     * Get this $barWidth
     *
     * @return int
     */
    public function getBarWidth()
    {
        return $this->barWidth;
    }

    /**
     * Set this $barWidth
     *
     * @param  int    $barWidth
     *
     * @return $this
     */
    public function setBarWidth($barWidth)
    {
        $this->barWidth = $barWidth;
        return $this;
    }
}
