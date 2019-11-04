<?php

namespace fpl\Web\Widget;

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
                    $dataset->toArray()['warn'] => (float) $dataset->getWarningThreshold()->getMax(),
                    $dataset->toArray()['crit'] => (float) $dataset->getCriticalThreshold()->getMax()
                ];
                $displayMax = array_search(max($potMax), $potMax);

                $graph[] = (new HorizontalBar($dataset->getLabel(), $dataset->getValue()))
                    ->setWarn((float) $dataset->getWarningThreshold()->getMax())
                    ->setCrit((float) $dataset->getCriticalThreshold()->getMax())
                    ->setForDisplay(
                        Perfdata::splitValue($dataset->toArray()['value'])[1],
                        Perfdata::splitValue($dataset->toArray()['value'])[2],
                        $displayMax
                    )
                    ->draw();
            } elseif ($dataset->getLabel() === 'pl') {
                $graph[] = (new HorizontalBar($dataset->getLabel(), $dataset->getValue()))
                    ->setWarn((float) $dataset->getWarningThreshold()->getMax())
                    ->setCrit((float) $dataset->getCriticalThreshold()->getMax())
                    ->setMax(100)
                    ->setForDisplay(
                        Perfdata::splitValue($dataset->toArray()['value'])[1],
                        Perfdata::splitValue($dataset->toArray()['value'])[2],
                        '')
                    ->draw();
            }
        }
        $this->setContent($graph);

        return $this;
    }
}
