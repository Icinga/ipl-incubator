<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

class PerfdataDisk extends BaseHtmlElement
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
            $potMax = [
                $dataset->toArray()['value'] => $dataset->getValue(),
                $dataset->toArray()['warn'] => (float)$dataset->getWarningThreshold()->getMax(),
                $dataset->toArray()['crit'] => (float)$dataset->getCriticalThreshold()->getMax()
            ];
            $displayMax = array_search(max($potMax), $potMax);

            $graph->addBar(
                (new HorizontalBar($dataset->getLabel(), $dataset->getValue()))
                    ->setWarn((float)$dataset->getWarningThreshold()->getMax())
                    ->setCrit((float)$dataset->getCriticalThreshold()->getMax())
                    ->setForDisplay($this->splitValue($dataset->toArray()['value'])[1],
                        $this->splitValue($dataset->toArray()['value'])[2],
                        $displayMax
                    )
            );
        }
        $this->setContent($graph->draw());

        return $this;
    }

    protected function splitValue($value)
    {
        preg_match('/(\d+\.?\d*)\s?(.*)/', $value, $matches);
        return $matches;
    }
}
