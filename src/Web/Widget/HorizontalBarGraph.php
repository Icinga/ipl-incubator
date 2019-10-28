<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class HorizontalBarGraph extends BaseHtmlElement
{
    protected $tag = 'svg';

    protected $defaultAttributes = ['class' => 'horizontal-bar-graph'];

    protected $bars;

    protected $title;

    protected $normalisedMax = -INF;

    protected $normalisedMin = INF;

    public function __construct($title)
    {
        $this->title = $title;
    }
    /**
     * Adds a bar
     *
     * @param string            $title
     * @param int|float         $value
     * @param string            $uom
     * @param int|float         $warn
     * @param int|float         $crit
     * @param int|float         $min
     * @param int|float         $max
     * @param array             $forDisplay     Array with keys: 'value', 'uom', 'max'
     *
     * @return $this
     */
    public function addDataSet($title, $value, $uom = null, $warn = null, $crit = null, $min = null, $max = null, $forDisplay = null)
    {
        $this->bars[] = [
            'title' => $title,
            'value' => $value,
            'uom' => $uom,
            'warn' => $warn,
            'crit' => $crit,
            'forDisplay' => $forDisplay
        ];

        $this->normalisedMax = max($this->normalisedMax, $value, $warn, $crit, $max);
        $this->normalisedMin = min(array_diff([$this->normalisedMin, $value, $warn, $crit, $min, 0], [null]));

        return $this;
    }

    public function draw()
    {
        if (isset($this->title)) {
            $graph[] = new HtmlElement(
                'text',
                new Attributes(['transform' => 'translate(0,' . HorizontalBar::BAR_WIDTH . ')']),
                new HtmlElement(
                    'tspan',
                    new Attributes(['class' => 'svg-title']),
                    $this->title
                )
            );
        }

        $bars = [];
        foreach ($this->bars as $key => $dataSet) {
            $position = $key;
            if (isset($this->title)) {
                $position += 1;
            }
            $bars[] = new HtmlElement(
                'g',
                new Attributes(['transform' => 'translate(0,' . $position * HorizontalBar::BAR_WIDTH * 2 . ')']),
                [
                    (
                        new HorizontalBar(
                            $dataSet['title'],
                            $dataSet['value'],
                            null,
                            $dataSet['uom'],
                            $dataSet['warn'],
                            $dataSet['crit'],
                            $this->normalisedMin,
                            $this->normalisedMax,
                            $dataSet['forDisplay']
                        )
                    )->setAttribute('viewbox', null)->draw()
                ]);
        }

        $graph[] = $bars;

        $amountBars = count($this->bars);
        if (isset($this->title)) {
            $amountBars += 1;
        }
        $this->addAttributes(['viewbox' => sprintf(
            '0 0 %s %s',
            500,
            $amountBars * (2 * HorizontalBar::OUTER_MARGIN_TOP + HorizontalBar::BAR_WIDTH))
        ]);

        $this->setContent($graph);

        return $this;
    }
}
