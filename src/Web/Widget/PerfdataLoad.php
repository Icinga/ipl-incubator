<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

class PerfdataLoad extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['id' => 'check-perfdata-load'];

    protected $perfdata;

    public function __construct($perfdata)
    {
        $this->perfdata = $perfdata;
    }

    public function draw()
    {
        $graph = new HorizontalBarGraph(null);
        foreach ($this->perfdata as $dataset) {
            $graph->addDataSet(
                $dataset->toArray()['label'],
                $dataset->toArray()['value'],
                $dataset->getUnit(),
                (float)$dataset->getWarningThreshold()->getRaw(),
                (float)$dataset->getCriticalThreshold()->getRaw()
            );
        }
        $this->setContent($graph->draw());

        return $this;
    }
}
