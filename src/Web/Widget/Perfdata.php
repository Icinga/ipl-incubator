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
            $displayedData = [];
            if ($verticalData = $this->calculateVerticalGraph($perfdata)) {
                $displayedData = (new VerticalBarGraph($verticalData['unit'], $verticalData['values']))->setLegend($verticalData['labels'])->draw();
            } else {
                foreach ($perfdata as $dataSet) {
                    $setArray = $this->formatDataSet($dataSet->toArray());
                    if ($this->isEligibleForHorizontalBar($setArray))
                    {
                        $potMax = [
                            $dataSet->toArray()['value'] => $dataSet->getValue(),
                            $dataSet->toArray()['warn'] => (float)$dataSet->getWarningThreshold()->getMax(),
                            $dataSet->toArray()['crit'] => (float)$dataSet->getCriticalThreshold()->getMax()
                        ];
                        $displayMax = array_search(max($potMax), $potMax);

                        $displayedData[] = (new HorizontalBar($dataSet->getLabel(), $dataSet->getValue()))
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
                        $displayedData[] = new HtmlElement('p', null, $dataSet->__toString());
                    }
                }
            }

            $this->setContent(new HtmlElement('div', new Attributes(['id' => 'check-perfdata-' . $command]), [
                $displayedData
            ]));
        }
    }

    /**
     * Whether the data is eligible for a bar
     *
     * @param $dataSet
     *
     * @return bool
     */
    protected function isEligibleForHorizontalBar($dataSet)
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

    /**
     * Returns of labels, values and the common unit on success
     * Returns null if the data is incompatible
     *
     * @param $perfdata
     *
     * @return array|null
     */
    protected function calculateVerticalGraph($perfdata)
    {
        $labels = [];
        $values = [];
        foreach ($perfdata as $dataSet) {
            if (
                (float)$dataSet->getWarningThreshold()->getMax()
                || (float)$dataSet->getCriticalThreshold()->getMax()
                || $dataSet->getMinimumValue()
                || $dataSet->getMaximumValue()
            ) {
                return null;
            } else {
                $values[] = $dataSet->getValue();
            }

            $units[] = $dataSet->getUnit();
            $labels[] = $dataSet->getLabel();
        }

        if (count(array_unique($units)) <= 1) {
            return ['labels' => $labels, 'values' => $values, 'unit' => $units[0]];
        }

        return null;
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

    /**
     * Splits a string into a float and the unit attached
     *
     * @param $value
     *
     * @return array
     */
    static function splitValue($value)
    {
        if (gettype($value) === 'double'|| gettype($value) === 'integer') {
            return [$value, $value, null];
        }
        preg_match('/(\d+\.?\d*)\s?(.*)/', $value, $matches);
        return $matches;
    }
}
