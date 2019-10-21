<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class HorizontalBar extends BaseHtmlElement
{
    protected $tag = 'svg';

    protected $defaultAttributes = ['class' => 'horizontal-bar-graph'];

    protected $data;

    protected $title;

    protected $graphData = [];

    protected $outerMarginLeft = 30;

    protected $outerMarginTop = 8;

    protected $totalWidth = 500;

    protected $textMargin = 5;

    protected $barWidth = 10;

    public function __construct($title, $value, Attributes $attributes = null, $uom = null, $warn = null, $crit = null, $min = null, $max = null)
    {
        $this->addAttributes($attributes);
        $this->addAttributes(['viewbox' => sprintf(
            '0 0 %s %s',
            $this->totalWidth,
            (2 * $this->outerMarginTop + $this->barWidth)
        )]);

        $this->title = $title;
        $this->setData($value, $uom, $warn, $crit, $min, $max);
    }

    public function setData($value, $uom = null, $warn = null, $crit = null, $min = null, $max = null)
    {
        $this->data['value'] = $value;
        $this->data['uom'] = $uom;

        $this->data['warn'] = $warn;
        $this->data['crit'] = $crit;

        $this->data['min'] = $min ?: min($value, 0);
        $this->data['max'] = $max ?: max($value, $warn, $crit);

        $this->calculateGraphData();
    }

    protected function calculateGraphData() {
        $this->graphData['bar-x'] = $this->outerMarginLeft + ($this->totalWidth - $this->outerMarginLeft) / 5;
        $this->graphData['bar-width'] = $this->totalWidth / 2;

        $this->graphData['zero'] = $this->getRelativeValue(0 - $this->data['min'], $this->data['max'] - $this->data['min'], $this->graphData['bar-width']);
    }

    public function draw()
    {
        $graph = [];

        $graph[] = $this->drawTitle();
        $graph[] = $this->drawGraph();
        $graph[] = $this->drawValues();

        $this->setContent($graph);

        return $this;
    }

    protected function drawTitle() {
        $title = new HtmlElement(
            'text',
            new Attributes([
                'class'         => 'svg-text',
                'fill'          => 'gray',
                'dominant-baseline' => 'central',
                'x'             => $this->outerMarginLeft + $this->textMargin,
                'y'             => $this->outerMarginTop + $this->barWidth / 2,
            ]),
            new HtmlElement(
                'tspan',
                [],
                $this->title
            )
        );

        return $title;
    }

    protected function drawGraph()
    {
        $graph = [
            new HtmlElement(
                'rect',
                new Attributes(
                    [
                        'x' => $this->graphData['bar-x'],
                        'y' => $this->outerMarginTop,
                        'width' => $this->graphData['bar-width'],
                        'height' => $this->barWidth,
                        'rx' => 4,
                        'fill' => 'lightgray'
                    ]
                )
            ),
            $this->drawBar()
        ];

        return $graph;
    }

    protected function drawBar()
    {
        $warn = $this->data['warn'];
        if ($warn !== null) {
            $warn = $this->drawThreshold($warn, 'warning');
        }

        $crit = $this->data['crit'];
        if ($crit !== null) {
            $crit = $this->drawThreshold($crit, 'critical');
        }

        $value = $this->getRelativeValue($this->data['value'], $this->data['max'] - $this->data['min'], $this->graphData['bar-width']);

        $start = min($this->graphData['zero'], $value);
        $end = max($this->graphData['zero'], $value);

        if ($this->data['min'] < 0) {
            if ($this->data['value'] > 0) {
                $path = sprintf(
                    'M0,0'
                    . 'l%s,0 '
                    . 'q4,0 4,4 '
                    . 'l0,%s '
                    . 'q0,4 -4,4 '
                    . 'l-%s,0',
                    $value - 4,
                    $this->barWidth - 8,
                    $value - 4
                );

                $xPos = $this->graphData['zero'];
            } else {
                $path = sprintf(
                    'M4,0'
                    . 'l%s,0 '
                    . 'l0,%s '
                    . 'l-%s,0 '
                    . 'q-4,0 -4,-4 '
                    . 'l0,-%s '
                    . 'q0,-4 4,-4',
                    - $value - 4,
                    $this->barWidth,
                    - $value - 4,
                    $this->barWidth - 8
                );

                $xPos = $this->graphData['zero'] + $value;
            }

            $bar = [
                new HtmlElement(
                    'path',
                    new Attributes(
                        [
                            'transform' => sprintf('translate(%s,%s)', $this->graphData['bar-x'] + $xPos, $this->outerMarginTop),
                            'height' => $this->barWidth,
                            'd' => $path,
                            'class' => sprintf('bar-%s', $this->getBarFill())
                        ]
                    )
                ),
                $warn,
                $crit,
            ];
        } else {
            $bar = [
                new HtmlElement(
                    'rect',
                    new Attributes(
                        [
                            'x' => $this->graphData['bar-x'],
                            'y' => $this->outerMarginTop,
                            'width' => $end - $start,
                            'height' => $this->barWidth,
                            'rx' => 4,
                            'class' => sprintf('bar-%s', $this->getBarFill())
                        ]
                    )
                ),
                $warn,
                $crit,
            ];
        }

        return $bar;
    }

    protected function drawThreshold($threshold, $kind)
    {
        $col = $kind;
        if ($this->getBarFill() === $kind) {
            $col = 'black';
        }

        if ($threshold === $this->data['max']) {
            $path = sprintf('M0.5,0.5'
                . 'l1,0 '
                . 'q4,0 4,4 '
                . 'l0,%s '
                . 'q0,4 -4,4 '
                . 'l-1,0',
            $this->barWidth - 9
            );

            return new HtmlElement(
                'path',
                new Attributes(
                    [
                        'transform' => sprintf('translate(%s,%s)', $this->graphData['bar-x'] + $this->graphData['bar-width'] - 5, $this->outerMarginTop),
                        'd' => $path,
                        'width' => 1,
                        'height' => $this->barWidth,
                        'class' => sprintf('threshold-%s round', $col)
                    ]
                )
            );
        }

        $threshold = $this->getRelativeValue($threshold, $this->data['max'] - $this->data['min'], $this->graphData['bar-width']);

        return new HtmlElement(
            'rect',
            new Attributes(
                [
                    'x' => $this->graphData['bar-x'] + $threshold + $this->graphData['zero'],
                    'y' => $this->outerMarginTop,
                    'width' => 1,
                    'height' => $this->barWidth,
                    'class' => sprintf('threshold-%s', $col)
                ]
            )
        );
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

    protected function getBarFill()
    {
        $value = $this->data['value'];
        $warn = $this->data['warn'];
        $crit = $this->data['crit'];

        if ($warn === null && $crit === null) {
            if ($this->data['value'] < 0) {
                return 'light-blue';
            }
            return 'blue';
        } elseif ($crit !== null && $value > $crit) {
            return 'critical';
        } elseif ($warn !== null && $value > $warn) {
            return 'warning';
        } else {
            return 'ok';
        }
    }

    protected function drawValues()
    {
        //todo: change font values to match mockups

        $unit = [];
        if (isset($this->data['uom'])) {
            $unit = new HtmlElement(
                'tspan',
                [],
                sprintf(' %s', $this->data['uom'])
            );
        }

        $max = [];
        if (isset($this->data['max'])) {
            $max = new HtmlElement(
                'tspan',
                [],
                sprintf(' / %s', $this->data['max'])
            );
        }


        $values = new HtmlElement(
            'text',
            new Attributes([
                'class'         => 'svg-text',
                'fill'          => 'gray',
                'dominant-baseline' => 'central',
                'x'             => $this->graphData['bar-x'] + $this->graphData['bar-width'] + $this->textMargin,
                'y'             => $this->outerMarginTop + $this->barWidth / 2,
            ]),
            [
                new HtmlElement(
                    'tspan',
                    [],
                    $this->data['value']
                ),
                $unit,
                $max
            ]
        );

        return $values;
    }
}
