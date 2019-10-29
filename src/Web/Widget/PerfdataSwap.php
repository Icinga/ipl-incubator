<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

class PerfdataSwap extends BaseHtmlElement
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
        $graph = [];
        foreach ($this->perfdata as $dataset) {
            $potMax = [
                $dataset->toArray()['value'] => $dataset->getValue(),
                $dataset->toArray()['warn'] => (float)$dataset->getWarningThreshold()->getMax(),
                $dataset->toArray()['crit'] => (float)$dataset->getCriticalThreshold()->getMax()
            ];
            $displayMax = array_search(max($potMax), $potMax);

            $graph[] = (new HorizontalBar(
                $dataset->getLabel(),
                $dataset->getValue(),
                null,
                null,
                (float)$dataset->getWarningThreshold()->getMax(),
                (float)$dataset->getCriticalThreshold()->getMax(),
                null,
                null,
                [
                    'value' => $this->splitValue($dataset->toArray()['value'])[0],
                    'uom' => $this->splitValue($dataset->toArray()['value'])[1],
                    'max' => $displayMax
                ]
            ))->draw();
        }
        $this->setContent($graph);

        return $this;
    }

    protected function splitValue($value)
    {
        return preg_split('/[ ]/', $value);
    }
}
