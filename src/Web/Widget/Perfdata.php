<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class Perfdata extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'perfdata-wrapper'];

    public function __construct($perfdata, $command)
    {

//add heading "Performance Data"
    switch ($command) {
        case 'load':
            $this->setContent((new PerfdataLoad($perfdata))->draw());
            break;
        case 'http':
            $this->setContent((new PerfdataHttp($perfdata))->draw());
            break;
        case 'disk':
            $this->setContent((new PerfdataDisk($perfdata))->draw());
            break;
        case 'ping':
            $this->setContent((new PerfdataPing($perfdata))->draw());
            break;
        case 'swap':
            $this->setContent((new PerfdataSwap($perfdata))->draw());
            break;
        default:
            $rows = [];
            foreach ($perfdata as $dataset) {
                $setArray = $this->formatDataSet($dataset->toArray());
                if ($this->isEligibleForGraph($setArray))
                {
                    $potMax = [
                        $dataset->toArray()['value'] => $dataset->getValue(),
                        $dataset->toArray()['warn'] => (float)$dataset->getWarningThreshold()->getMax(),
                        $dataset->toArray()['crit'] => (float)$dataset->getCriticalThreshold()->getMax()
                    ];
                    $displayMax = array_search(max($potMax), $potMax);

                    $rows[] = (new HorizontalBar(
                        $dataset->getLabel(),
                        $dataset->getValue(),
                        null,
                        null,
                        (float)$dataset->getWarningThreshold()->getMax(),
                        (float)$dataset->getCriticalThreshold()->getMax(),
                        null,
                        null,
                        [
                            'value' => $this->splitValue($dataset->toArray()['value'])[1],
                            'uom' => $this->splitValue($dataset->toArray()['value'])[2],
                            'max' => $displayMax
                        ]
                    ))->draw();

                } else {
                    // todo: format
                    $rows[] = new HtmlElement('p', null, $dataset->__toString());
                }
            }

            $this->setContent(new HtmlElement('div', new Attributes(['id' => 'check-perfdata-' . $command]), [
                $rows
            ]));
        }
    }


    protected function isEligibleForGraph($dataSet)
    {
        if (isset($dataSet['warn']) && $dataSet['warn'] !== null) {
            return true;
        }

        if (isset($dataSet['crit']) && $dataSet['crit'] !== null) {
            return true;
        }

        if (isset($dataSet['min']) && $dataSet['min']) {
            return true;
        }

        if (isset($dataSet['max']) && $dataSet['max'] !== null) {
            return true;
        }

        return false;
    }

    protected function formatDataSet($dataSet)
    {
        foreach ($dataSet as $key => &$value) {
            if ($value === '') {
                $value = null;
                continue;
            }

            if ($key !== 'value' && $key !== 'label' && is_string($value)) {
                $value = (float)$this->splitValue($value)[0];
            }
        }

        return $dataSet;
    }

    protected function splitValue($value)
    {
        preg_match('/(\d+\.?\d*)\s?(.*)/', $value, $matches);
        return $matches;
    }
}
