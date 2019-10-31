<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class PerfdataIcinga extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['id' => 'check-perfdata-http'];

    protected $size;

    protected $graph;

    public function __construct($perfdata)
    {
        // todo if all values in a graph are 0 => set default scale of 1

        $perfdataArray = [];

        $itemRateValues = [];
        $amountValues = [];
        $apiNumLegendItems = [];

        $activeServicesValues = [];
        $activeHostsValues = [];

        $passiveServicesValues = [];
        $passiveHostsValues = [];

        $idoQueryTimeValues = [];
        $idoQueryQueueValues = [];
        foreach ($perfdata as $key => $dataset) {
            $labelRaw = $dataset->getLabel();

            if (preg_match('/api_num_json_rpc_(.*)_queue_(item.*)/', $labelRaw, $apiNumJsonRpc)) {
                if ($apiNumJsonRpc[2] === 'item_rate') {
                    $itemRateValues[] = round($dataset->getValue(), 2);
                } elseif ($apiNumJsonRpc[2] === 'items') {
                    $amountValues[] = round($dataset->getValue(), 2);
                } else {
                    // todo maybe make a misc table then? or another dataset?
                    var_dump($dataset);
                    continue;
                }

                if (! in_array($apiNumJsonRpc[1] . ' queue', $apiNumLegendItems)) {
                    $apiNumLegendItems[] = $apiNumJsonRpc[1] . ' queue';
                }
                continue;
            }

            if (preg_match('/active_(.*)_checks_(.*)/', $labelRaw, $activeChecksTime)) {
                $num = ['1min' => 0, '5min' => 1, '15min' => 2];
                if ($activeChecksTime[1] === 'service') {
                    $activeServicesValues[$num[$activeChecksTime[2]]] = round($dataset->getValue(), 2);
                } elseif ($activeChecksTime[1] === 'host') {
                    $activeHostsValues[$num[$activeChecksTime[2]]] = round($dataset->getValue(), 2);
                } else {
                    // todo maybe make a misc table then? or another dataset?
                    var_dump($dataset);
                    continue;
                }
                continue;
            }

            if (preg_match('/passive_(.*)_checks_(.*)/', $labelRaw, $passiveChecksTime)) {
                $num = ['1min' => 0, '5min' => 1, '15min' => 2];
                if ($passiveChecksTime[1] === 'service') {
                    $passiveServicesValues[$num[$passiveChecksTime[2]]] = round($dataset->getValue(), 2);
                } elseif ($passiveChecksTime[1] === 'host') {
                    $passiveHostsValues[$num[$passiveChecksTime[2]]] = round($dataset->getValue(), 2);
                } else {
                    // todo maybe make a misc table then? or another dataset?
                    var_dump($dataset);
                    continue;
                }
                continue;
            }

            if (preg_match('/idomysqlconnection_ido-mysql_(query_queue|queries)_(.*)/', $labelRaw, $idoQuery)) {
                if ($idoQuery[1] === 'queries' && $idoQuery[2] !== 'rate') {
                    $num = ['1min' => 0, '5mins' => 1, '15mins' => 2];
                    $idoQueryTimeValues[$num[$idoQuery[2]]] = round($dataset->getValue(), 2);
                } elseif ($idoQuery[1] === 'query_queue' || ($idoQuery[1] === 'queries' && $idoQuery[2] === 'rate')) {
                    $idoQueryQueueValues[] = round($dataset->getValue(), 2);
                } else {
                    // todo maybe make a misc table then? or another dataset?
                    var_dump($dataset);
                    continue;
                }
            }


//    $itemRateValues[] = round($dataset->getValue(), 2);
//    $amountValues[] = round($dataset->getValue(), 2);
//    $perfdataArray[] = [$dataset->toArray()['label'], $dataset->toArray()['value']];
        }
//        $perfdataStr = '';
//        foreach ($perfdataArray as $value) {
//            $perfdataStr .= PHP_EOL . $value[0];
//        }
//        var_dump($perfdataArray);
//        echo $perfdataStr;

        $graph[] = (new VerticalBarGraph('item rates', $itemRateValues))->addDataSet('amount items', $amountValues)->setLegend($apiNumLegendItems)->draw();
        $graph[] =
            (new VerticalBarGraph('active service checks', $activeServicesValues))
            ->addDataSet('active host checks', $activeHostsValues)
            ->setLegend(['1 min', '5 min', '15 min'])
            ->draw();

        $graph[] =
            (new VerticalBarGraph('passive service checks', $passiveServicesValues))
            ->addDataSet('passive host checks', $passiveHostsValues)
            ->setLegend(['1 min', '5 min', '15 min'])
            ->draw();

        $graph[] =
            (new VerticalBarGraph('ido query times', $idoQueryTimeValues))
            ->setLegend(['1 min', '5 min', '15 min'])
            ->draw();

        $graph[] =
            (new VerticalBarGraph('ido query queue', $idoQueryQueueValues))
            ->setLegend(['queries rate', 'item count', 'item rate'])
            ->draw();

        $this->graph = $graph;
    }

    public function draw()
    {
        $content = [
            $this->graph
        ];

        $this->setContent($content);

        return $this;
    }
}
