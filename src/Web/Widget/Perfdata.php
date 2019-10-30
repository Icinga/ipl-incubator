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

// todo ? add heading "Performance Data"
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
            foreach ($perfdata as $dataSet) {
                $setArray = $this->formatDataSet($dataSet->toArray());
                if ($this->isEligibleForGraph($setArray))
                {
                    $potMax = [
                        $dataSet->toArray()['value'] => $dataSet->getValue(),
                        $dataSet->toArray()['warn'] => (float)$dataSet->getWarningThreshold()->getMax(),
                        $dataSet->toArray()['crit'] => (float)$dataSet->getCriticalThreshold()->getMax()
                    ];
                    $displayMax = array_search(max($potMax), $potMax);

                    $rows[] = (new HorizontalBar(
                        $dataSet->getLabel(),
                        $dataSet->getValue()))
                        ->setWarn((float)$dataSet->getWarningThreshold()->getMax())
                        ->setCrit((float)$dataSet->getCriticalThreshold()->getMax())
                        ->setMin($dataSet->getMinimumValue())
                        ->setMax($dataSet->getMaximumValue())
                        ->setForDisplay(
                            Perfdata::splitValue($dataSet->toArray()['value'])[1],
                            Perfdata::splitValue($dataSet->toArray()['value'])[2],
                            $displayMax
                        )
                        ->draw();
                } else {
                    // todo ? format
                    $rows[] = new HtmlElement('p', null, $dataSet->__toString());
                }
            }

            $this->setContent(new HtmlElement('div', new Attributes(['id' => 'check-perfdata-' . $command]), [
                $rows
            ]));
        }
    }

    protected function isEligibleForGraph($dataSet)
    {
        // todo: check if is eligible for vertical graph!!!

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
                $value = (float)Perfdata::splitValue($value)[1];
            }
        }

        return $dataSet;
    }

    static function splitValue($value)
    {
        preg_match('/(\d+\.?\d*)\s?(.*)/', $value, $matches);
        return $matches;
    }
}
