<?php

namespace fpl\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class PerfdataHttp extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['id' => 'check-perfdata-http'];

    protected $size;

    protected $graph;

    public function __construct($perfdata)
    {
        $values = [];
        $labels = [];
        foreach ($perfdata as $key => $dataset) {

            if ($dataset->getLabel() === 'size') {
                $this->size = $dataset->toArray()['value'];
            } elseif (substr($dataset->getLabel(), 0, 5) === 'time_') {
                $values[] = round($dataset->getValue() * 1000, 2);
                $labels[] = substr($dataset->getLabel(), 5);
            } else {
                $values[] = round($dataset->getValue() * 1000, 2);
                $labels[] = $dataset->getLabel();
            }
        }

        $this->graph = (new VerticalBarGraph('times in ms', $values))->setLegend($labels);
    }

    public function draw()
    {
        $content = [
            new HtmlElement('p', null, 'size: ' . $this->size),
            $this->graph->draw()
        ];

        $this->setContent($content);

        return $this;
    }
}
