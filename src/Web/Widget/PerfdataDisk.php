<?php

namespace ipl\Web\Widget;

use Icinga\Util\Format;
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

            $graph->addDataSet(
                $dataset->getLabel(),
                $dataset->getValue(),
                $dataset->getUnit(),
                (float)$dataset->getWarningThreshold()->getMax(),
                (float)$dataset->getCriticalThreshold()->getMax(),
                null,
                null,
                [
                    'value' => $this->splitValue($dataset->toArray()['value'])[0],
                    'uom' => $this->splitValue($dataset->toArray()['value'])[1],
                    'max' => $displayMax
                ]
            );
        }
        $this->setContent($graph->draw());

        return $this;
    }

    protected function splitValue($value)
    {
        return preg_split('/[ ]/', $value);
    }
}
