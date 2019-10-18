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

    protected $outerMarginLeft = 30;

    protected $outerMarginTop = 8;

    protected $totalWidth = 500;

    protected $textMargin = 5;

    protected $barWidth = 10;

    public function __construct($title, $data, Attributes $attributes = null)
    {
        $this->addAttributes($attributes);
        $this->addAttributes(['viewbox' => '0 0 ' . $this->totalWidth . ' ' . (2 * $this->outerMarginTop + $this->barWidth)]);
        $this->setData($data);
        $this->title = $title;

    }

    public function setData($data)
    {
        //todo: verify!!!

        $this->data['value'] = $data[0];
        $this->data['uom'] = $data[1];   // null valid

        $this->data['warn'] = $data[2];  // null valid
        $this->data['crit'] = $data[3];  // null valid

        $this->data['min'] = $data[4];   // if no min given -> if if 0 smaller min -> min
        $this->data['max'] = $data[5];   // if no max given -> largest value
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
                        'x' => $this->outerMarginLeft + ($this->totalWidth - $this->outerMarginLeft) / 5,
                        'y' => $this->outerMarginTop,
                        'width' => $this->totalWidth / 2,
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
        $barLength = $this->totalWidth / 2;
        $length = $this->getRelativeValue($this->data['value'],  $this->data['max'], $barLength);

        $warn = $this->data['warn'];
        if ($warn !== null) {
            $warn = $this->drawThreshold($warn, 'warning');
        }

        $crit = $this->data['crit'];
        if ($crit !== null) {
            $crit = $this->drawThreshold($crit, 'critical');
        }

        $bar = [
            new HtmlElement(
                'rect',
                new Attributes(
                    [
                        'x' => $this->outerMarginLeft + ($this->totalWidth - $this->outerMarginLeft) / 5,
                        'y' => $this->outerMarginTop,
                        'width' => $length,
                        'height' => $this->barWidth,
                        'rx' => 4,
                        'class' => sprintf('bar-%s', $this->getBarFill())
                    ]
                )
            ),
            $warn,
            $crit,
        ];

        return $bar;
    }

    protected function drawThreshold($threshold, $kind)
    {
        $barLength = $this->totalWidth / 2;

        $col = $kind;
        if ($this->getBarFill() === $kind) {
            $col = 'black';
        }

        if ($threshold === $this->data['max']) {
            $path = 'M0.5,0.5'
                . 'l1,0 '
                . 'q3,0 3,3 '
                . 'l0,3 '
                . 'q0,3 -3,3 '
                . 'l-1,0';

            return new HtmlElement(
                'path',
                new Attributes(
                    [
                        'transform' => sprintf('translate(%s,%s)', $this->outerMarginLeft + ($this->totalWidth - $this->outerMarginLeft) / 5 + $barLength - 5, $this->outerMarginTop),
                        'd' => $path,
                        'width' => 1,
                        'height' => $this->barWidth,
                        'class' => sprintf('threshold-%s round', $col)
                    ]
                )
            );
        }

        $threshold = $this->getRelativeValue($threshold,  $this->data['max'], $barLength);

        return new HtmlElement(
            'rect',
            new Attributes(
                [
                    'x' => $this->outerMarginLeft + ($this->totalWidth - $this->outerMarginLeft) / 5 + $threshold,
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
                'x'             => $this->outerMarginLeft + ($this->totalWidth - $this->outerMarginLeft) / 5 + $this->totalWidth / 2 + $this->textMargin,
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
