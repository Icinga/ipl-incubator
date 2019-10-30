<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class HorizontalBar extends BaseHtmlElement
{
    const BAR_WIDTH = 10;

    const OUTER_MARGIN_TOP = 0;

    protected $tag = 'svg';

    protected $defaultAttributes = ['class' => 'horizontal-bar'];

    /**
     * Perfdata in the form of an array
     * Keys: value, uom, warn, crit, min, max
     *
     * @var array
     */
    protected $data = [
        'value'       => null,
        'uom'         => null,
        'warn'        => null,
        'crit'        => null,
        'min'         => null,
        'max'         => null
    ];

    /**
     * Title of this bar
     *
     * @var string
     */
    protected $title;

    /**
     * Calculated data required for drawing the graph
     * Keys: zero, bar-x, bar-width, value, displayValue, displayMax, displayUom
     *
     * @var array
     */
    protected $graphData = [];

    /**
     * Width of the bar
     *
     * @var int
     */
    protected $barWidth = self::BAR_WIDTH;

    /**
     * Margin to the left of the svg
     *
     * @var int
     */
    protected $outerMarginLeft = 0;

    /**
     * Margin to the top of the svg
     *
     * @var int
     */
    protected $outerMarginTop = self::OUTER_MARGIN_TOP;

    /**
     * Total width of the svg which is calculated during drawing
     *
     * @var
     */
    protected $totalWidth = 500;

    /**
     * Margin between text and graphical objects
     *
     * @var int
     */
    protected $textMargin = 5;

    /**
     * Whether over or under the threshold
     *
     * @var bool
     */
    protected $inverted = false;

    /**
     * HorizontalBar constructor.
     *
     * @param string            $title
     * @param int|float         $value
     * @param Attributes|null   $attributes
     */
    public function __construct($title, $value, Attributes $attributes = null)
    {
        $this->addAttributes($attributes);
        $this->addAttributes(['viewbox' => sprintf(
            '0 0 %s %s',
            $this->totalWidth,
            (2 * $this->outerMarginTop + $this->barWidth)
        )]);

        $this->title = $title;
        $this->data['value'] = $value;
    }

    /**
     * Draws this graph
     *
     * @return $this
     */
    public function draw()
    {
        $this->calculateGraphData();

        $graph = [];

        $graph[] = $this->drawTitle();
        $graph[] = $this->drawBar();
        $graph[] = $this->drawValues();

        $this->setContent($graph);

        return $this;
    }

    /**
     * Calculates data needed for drawing
     */
    protected function calculateGraphData() {

        if ($this->data['crit'] !== null && $this->data['warn'] > $this->data['crit']) {
            $this->setInverted(true);
        }

        $this->graphData['bar-x'] = $this->outerMarginLeft + ($this->totalWidth - $this->outerMarginLeft) / 5;
        $this->graphData['bar-width'] = $this->totalWidth / 2;

        $this->graphData['min'] = $this->data['min'] ?: min($this->data['value'], 0);
        $this->graphData['max'] = $this->data['max'] ?: max($this->data['value'], $this->data['warn'], $this->data['crit']);

        $this->graphData['zero'] = $this->getRelativeValue(0 - $this->graphData['min'], $this->graphData['max'] - $this->graphData['min'], $this->graphData['bar-width']);

        if (! isset($this->graphData['displayValue']))
        {
            $this->graphData['displayValue'] = $this->data['value'];
        }

        if (! isset($this->graphData['displayMax']))
        {
            $this->graphData['displayMax'] = $this->data['max'];
        }

        if (! isset($this->graphData['displayUom']))
        {
            $this->graphData['displayUom'] = $this->data['uom'];
        }
    }

    /**
     * @return HtmlElement
     */
    protected function drawTitle() {
        $title = new HtmlElement(
            'text',
            new Attributes([
                'class'         => 'svg-horizontal-title',
                'fill'          => 'gray',
                'dominant-baseline' => 'central',
                'x'             => $this->outerMarginLeft ? $this->outerMarginTop + $this->textMargin : 0,
                'y'             => $this->outerMarginTop + $this->barWidth / 2,
            ]),
            new HtmlElement(
                'tspan',
                new Attributes(['class' => 'svg-horizontal-label']),
                $this->title
            )
        );

        return $title;
    }

    /**
     * @return array
     */
    protected function drawBar()
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
            $this->drawFill()
        ];

        return $graph;
    }

    /**
     * @return array
     */
    protected function drawFill()
    {
        $warn = $this->data['warn'];
        if ($warn !== null) {
            $warn = $this->drawThreshold($warn, 'warning');
        }

        $crit = $this->data['crit'];
        if ($crit !== null) {
            $crit = $this->drawThreshold($crit, 'critical');
        }

        $value = $this->getRelativeValue($this->data['value'], $this->graphData['max'] - $this->graphData['min'], $this->graphData['bar-width']);

        $start = min($this->graphData['zero'], $value);
        $end = max($this->graphData['zero'], $value);

        if ($this->graphData['min'] < 0) {
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

    /**
     * Draws a threshold line in fitting colour and curves it for when it reaches max value
     *
     * @param int      $threshold   Absolute threshold value
     * @param string   $kind        Either warning or critical
     *
     * @return HtmlElement
     */
    protected function drawThreshold($threshold, $kind)
    {
        $col = $kind;
        if ($this->getBarFill() === $kind && ! $this->isInverted()) {
            $col = 'black';
        }

        if ($threshold === $this->graphData['max']) {
            $path = sprintf(
                'M0.5,0.5'
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

        $threshold = $this->getRelativeValue($threshold, $this->graphData['max'] - $this->graphData['min'], $this->graphData['bar-width']);

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

    /**
     * @return string either neutral, ok, warning, critical
     */
    protected function getBarFill()
    {
        $value = $this->data['value'];
        $warn = $this->data['warn'];
        $crit = $this->data['crit'];

        if ($warn === null && $crit === null) {
            if ($this->data['value'] < 0) {
                return 'light-neutral';
            }
            return 'neutral';
        }

        if (! $this->isInverted()) {
            if ($crit !== null && $value > $crit) {
                return 'critical';
            } elseif ($warn !== null && $value > $warn) {
                return 'warning';
            } else {
                return 'ok';
            }
        } else {
            if ($crit !== null && $value < $crit) {
                return 'critical';
            } elseif ($warn !== null && $value < $warn) {
                return 'warning';
            } else {
                return 'ok';
            }
        }
    }

    /**
     * @return HtmlElement
     */
    protected function drawValues()
    {
        $unit = [];
        if (isset($this->graphData['displayUom'])) {
            $unit = new HtmlElement(
                'tspan',
                new Attributes(['class' => 'svg-horizontal-uom']),
                sprintf(' %s', $this->graphData['displayUom'])
            );
        }

        $max = [];
        if (isset($this->graphData['displayMax']) && $this->graphData['displayMax'] !== '') {
            $max = new HtmlElement(
                'tspan',
                new Attributes(['class' => 'svg-horizontal-max']),
                sprintf(' / %s', $this->graphData['displayMax'])
            );
        }

        $values = new HtmlElement(
            'text',
            new Attributes([
                'class'         => 'svg-text',
                'x'             => $this->graphData['bar-x'] + $this->graphData['bar-width'] + $this->textMargin,
                'y'             => $this->outerMarginTop + $this->barWidth / 2 + 4,
            ]),
            [
                new HtmlElement(
                    'tspan',
                    new Attributes(['class' => 'svg-horizontal-value']),
                    $this->graphData['displayValue']
                ),
                $unit,
                $max
            ]
        );

        return $values;
    }

    /**
     * Get this $inverted
     *
     * @return bool
     */
    public function isInverted()
    {
        return $this->inverted;
    }

    /**
     * Set this $inverted
     *
     * @param bool $inverted
     *
     * @return $this
     */
    public function setInverted($inverted)
    {
        $this->inverted = $inverted;

        return $this;
    }

    public function getValue()
    {
        return $this->data['value'];
    }

    public function setUom($uom)
    {
        $this->data['uom'] = $uom;

        return $this;
    }

    public function getWarn()
    {
        return $this->data['warn'];
    }

    public function setWarn($warn)
    {
        $this->data['warn'] = $warn;

        return $this;
    }

    public function setCrit($crit)
    {
        $this->data['crit'] = $crit;

        return $this;
    }

    public function getCrit()
    {
        return $this->data['crit'];
    }

    public function getMin()
    {
        return $this->data['min'];
    }

    public function setMin($min)
    {
        $this->data['min'] = $min;

        return $this;
    }

    public function getMax()
    {
        return $this->data['max'];
    }

    public function setMax($max)
    {
        $this->data['max'] = $max;

        return $this;
    }

    public function setForDisplay($value = null, $uom = null, $max = null)
    {
        $this->graphData['displayValue'] = $value;
        $this->graphData['displayMax'] = $max;
        $this->graphData['displayUom'] = $uom;

        return $this;
    }
}
