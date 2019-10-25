<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

class PerfdataProcs extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['id' => 'check-perfdata-procs'];

    protected $perfdata;

    public function __construct($perfdata)
    {
        $this->perfdata = $perfdata;
    }

    public function draw()
    {
        $graph = [];
        foreach ($this->perfdata as $dataset) {
            $graph[] = (new HorizontalBar(
                $dataset->getLabel(),
                $dataset->toArray()['value'],
                null,
                $dataset->getUnit(),
                (float)$dataset->getWarningThreshold()->getRaw(),
                (float)$dataset->getCriticalThreshold()->getRaw()
            ))->draw();
        }
        $this->setContent($graph);

        return $this;
    }
}
