<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class HorizontalBarGraph extends BaseHtmlElement
{
    protected $tag = 'div';

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
     * @param HorizontalBar $bar
     *
     * @return $this
     */
    public function addBar(HorizontalBar $bar)
    {
        $this->bars[] = $bar;

        $this->normalisedMax = max($this->normalisedMax, $bar->getValue(), $bar->getWarn(), $bar->getCrit(), $bar->getMax());
        $this->normalisedMin = min(array_diff([$this->normalisedMin, $bar->getValue(), $bar->getWarn(), $bar->getCrit(), $bar->getMin(), 0], [null]));

        return $this;
    }

    public function draw()
    {
        if (isset($this->title)) {
            $graph[] = new HtmlElement(
                'span',
                new Attributes(['class' => 'perfdata-set-title']),
                $this->title
            );
        }

        $bars = [];
        foreach ($this->bars as $bar) {
            if ($bar instanceof HorizontalBar) {
                $bars[] = $bar
                    ->setMin($this->normalisedMin)
                    ->setMax($this->normalisedMax)
                    ->draw();
            } else {
                $bars[] = (new HorizontalBar($bar['title'], $bar['value']))
                    ->setUom($bar['uom'])
                    ->setWarn($bar['warn'])
                    ->setCrit($bar['crit'])
                    ->setMin($this->normalisedMin)
                    ->setMax($this->normalisedMax)
                    ->setForDisplay($bar['forDisplay'])
                    ->draw();
            }
        }

        $graph[] = $bars;
        $this->setContent($graph);

        return $this;
    }
}
