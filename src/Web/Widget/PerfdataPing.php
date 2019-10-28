<?php

namespace ipl\Web\Widget;

use Icinga\Util\Format;
use ipl\Html\BaseHtmlElement;

class PerfdataPing extends BaseHtmlElement
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

            if ($dataset->getLabel() === 'rta') {

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
                    $dataset->getUnit(),
                    (float)$dataset->getWarningThreshold()->getMax(),
                    (float)$dataset->getCriticalThreshold()->getMax(),
                    null,
                    null,
                    [
                        'value' => $dataset->toArray()['value'],
                        'uom' => '',
                        'max' => $displayMax
                    ]
                ))->draw();
            } elseif ($dataset->getLabel() === 'pl') {
                $graph[] = (new HorizontalBar(
                    $dataset->getLabel(),
                    $dataset->getValue(),
                    null,
                    $dataset->getUnit(),
                    (float)$dataset->getWarningThreshold()->getMax(),
                    (float)$dataset->getCriticalThreshold()->getMax(),
                    null,
                    100,
                    [
                        'value' => $dataset->toArray()['value'],
                        'uom' => '',
                        'max' => '',
                    ]
                ))->draw();
            }
        }
        $this->setContent($graph);

        return $this;
    }

    protected function splitValue($value)
    {
        return preg_split('/[ ]/', $value);
    }
}