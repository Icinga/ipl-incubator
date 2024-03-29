<?php

namespace fpl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use \Icinga\Util\Format;

class PerfdataIcinga extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['id' => 'check-perfdata-http'];

    protected $size;

    protected $data;

    public function __construct($perfdata)
    {
        $numServicesStates = [];
        $labelsServicesStates = [];
        $numHostsStates = [];
        $labelsHostsStates = [];

        $itemRateValues = [];
        $amountValues = [];
        $apiNumLegendItems = [];

        $activeServicesValues = [];
        $activeHostsValues = [];

        $passiveServicesValues = [];
        $passiveHostsValues = [];

        $idoQueryTimeValues = [];
        $idoQueryQueueValues = [];

        $latency = [];
        $executionTime = [];

        $lastMessages = [];
        $sumBytes = [];
        $sumMessages = [];

        $apiEndpoints = [];
        $apiEndpointsLabels = [];
        $apiClients = [];
        $apiClientsLabels = [];
        foreach ($perfdata as $key => $dataset) {
            $labelRaw = $dataset->getLabel();
            if (preg_match('/api_num_{0,1}(.*)_(endpoints|clients)/', $labelRaw, $apiEndpointsClients)) {
                if ($apiEndpointsClients[2] === 'endpoints') {
                    $apiEndpoints[] = round($dataset->getValue(), 2);
                    $apiEndpointsLabels[] = $apiEndpointsClients[1] ?: 'total';
                } elseif ($apiEndpointsClients[2] === 'clients') {
                    $apiClients[] = round($dataset->getValue(), 2);
                    $apiClientsLabels[] = $apiEndpointsClients[1];
                } else {
                    $this->displayMiscData($dataset);
                    continue;
                }
                continue;
            }

            if (preg_match('/api_num_json_rpc_(.*)_queue_(item.*)/', $labelRaw, $apiNumJsonRpc)) {
                if ($apiNumJsonRpc[2] === 'item_rate') {
                    $itemRateValues[] = round($dataset->getValue(), 2);
                } elseif ($apiNumJsonRpc[2] === 'items') {
                    $amountValues[] = round($dataset->getValue(), 2);
                } else {
                    $this->displayMiscData($dataset);
                    continue;
                }

                if (! in_array($apiNumJsonRpc[1] . ' queue', $apiNumLegendItems)) {
                    $apiNumLegendItems[] = $apiNumJsonRpc[1] . ' queue';
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
                    $this->displayMiscData($dataset);
                    continue;
                }
                continue;
            }

            if (preg_match('/active_(.*)_checks(.*)/', $labelRaw, $activeChecksTime)) {
                $num = ['_1min' => 0, '_5min' => 1, '_15min' => 2, '' => 3];
                if ($activeChecksTime[1] === 'service') {
                    $activeServicesValues[$num[$activeChecksTime[2]]] = round($dataset->getValue(), 2);
                } elseif ($activeChecksTime[1] === 'host') {
                    $activeHostsValues[$num[$activeChecksTime[2]]] = round($dataset->getValue(), 2);
                } else {
                    $this->displayMiscData($dataset);
                    continue;
                }
                continue;
            }

            if (preg_match('/passive_(.*)_checks(.*)/', $labelRaw, $passiveChecksTime)) {
                $num = ['_1min' => 0, '_5min' => 1, '_15min' => 2, '' => 3];
                if ($passiveChecksTime[1] === 'service') {
                    $passiveServicesValues[$num[$passiveChecksTime[2]]] = round($dataset->getValue(), 2);
                } elseif ($passiveChecksTime[1] === 'host') {
                    $passiveHostsValues[$num[$passiveChecksTime[2]]] = round($dataset->getValue(), 2);
                } else {
                    $this->displayMiscData($dataset);
                    continue;
                }
                continue;
            }

            if (preg_match('/^(min)_(.*)|(max)_(.*)|(avg)_(.*)/', $labelRaw, $minMaxAvg)) {
                $minMaxAvg = array_values(array_filter($minMaxAvg));

                if ($minMaxAvg[2] === 'latency') {
                    $latency[$minMaxAvg[1]] = $dataset->getValue();
                } elseif ($minMaxAvg[2] === 'execution_time') {
                    $executionTime[$minMaxAvg[1]] = $dataset->getValue();
                } else {
                    $this->displayMiscData($dataset);
                    continue;
                }
                continue;
            }

            if (preg_match('/num_(services|hosts)_(.*)/', $labelRaw, $numMonitoringObjectsState)) {
                $states = ['up', 'down', 'unreachable', 'ok', 'warning', 'critical', 'unknown', 'pending'];
                if ($numMonitoringObjectsState[1] === 'services' && ! in_array($numMonitoringObjectsState[2], $states)) {
                    $numServicesStates[] = $dataset->getValue();
                    $labelsServicesStates[] = $numMonitoringObjectsState[2];
                } elseif ($numMonitoringObjectsState[1] === 'hosts' && ! in_array($numMonitoringObjectsState[2], $states)) {
                    $numHostsStates[] = $dataset->getValue();
                } else {
                    continue;
                }
                continue;
            }

            if (preg_match('/(.*)_(sent|received).*/', $labelRaw, $sentReceived)) {
                if ($sentReceived[1] === 'last_messages') {
                    $lastMessages[] = round($dataset->getValue(), 2);
                } elseif ($sentReceived[1] === 'sum_bytes') {
                    $sumBytes[] = round($dataset->getValue(), 2);
                } elseif ($sentReceived[1] === 'sum_messages') {
                    $sumMessages[] = round($dataset->getValue(), 2);
                } else {
                    $this->displayMiscData($dataset);
                    continue;
                }
                continue;
            }
        }
//  debug stuff!
//        $perfdataStr = '';
//        foreach ($perfdata as $value) {
//            $perfdataStr .= '<br>' . $value->toArray()['label'];
//        }
//        echo $perfdataStr;

        $content[] = (new HtmlElement('h3', new Attributes(['class' => 'perfdata-heading']), 'Host and Service States:'));
        $content[] =
            (new VerticalBarGraph('numbers services', $numServicesStates))
                ->addDataSet('number hosts', $numHostsStates)
                ->setLegend(array_merge($labelsServicesStates, $labelsHostsStates))
                ->draw();

        $content[] = (new HtmlElement('h3', new Attributes(['class' => 'perfdata-heading']), 'Json API RPC:'));
        $content[] =
            (new VerticalBarGraph('item rates', $itemRateValues))
                ->addDataSet('amount items', $amountValues)
                ->setLegend($apiNumLegendItems)->draw();

        $content[] = (new HtmlElement('h3', new Attributes(['class' => 'perfdata-heading']), 'Active Checks:'));
        $content[] =
            (new VerticalBarGraph('services', $activeServicesValues))
                ->addDataSet('hosts', $activeHostsValues)
                ->setLegend(['1 min', '5 min', '15 min', 'per second'])
                ->draw();

        $content[] = (new HtmlElement('h3', new Attributes(['class' => 'perfdata-heading']), 'Passive Checks:'));
        $content[] =
            (new VerticalBarGraph('services', $passiveServicesValues))
                ->addDataSet('hosts', $passiveHostsValues)
                ->setLegend(['1 min', '5 min', '15 min', 'per second'])
                ->draw();

        $content[] = (new HtmlElement('h3', new Attributes(['class' => 'perfdata-heading']), 'IDO MySQL queries:'));
        $content[] =
            (new VerticalBarGraph('query times', $idoQueryTimeValues))
                ->setLegend(['1 min', '5 min', '15 min'])
                ->addAttributes(['class' => 'svg-half'])
                ->draw();
        $content[] =
            (new VerticalBarGraph('query queue', $idoQueryQueueValues))
                ->setLegend(['queries rate', 'item count', 'item rate'])
                ->addAttributes(['class' => 'svg-half'])
                ->draw();

        $content[] = (new HtmlElement('h3', new Attributes(['class' => 'perfdata-heading']), 'Min | Avg | Max'));
        $content[] = (new HorizontalBar('Latency', $latency['avg']))
            ->setMin($latency['min'])
            ->setMax($latency['max'])
            ->setForDisplay(Format::seconds($latency['avg']), '', Format::seconds($latency['max']))
            ->draw();
        $content[] = (new HorizontalBar('Execution time', $executionTime['avg']))
            ->setMin($executionTime['min'])
            ->setMax($executionTime['max'])
            ->setForDisplay(Format::seconds($executionTime['avg']), '', Format::seconds($executionTime['max']))
            ->draw();

        $content[] = (new HtmlElement('h3', new Attributes(['class' => 'perfdata-heading']), 'Sent and Received:'));
        $content[] =
            (new VerticalBarGraph('last messages', $lastMessages))
                ->addDataSet('sum bytes', $sumBytes)
                ->addDataSet('sum messages', $sumMessages)
                ->setLegend(['sent', 'received'])
                ->draw();

        $content[] = (new HtmlElement('h3', new Attributes(['class' => 'perfdata-heading']), 'Api Endpoints and Clients'));
        $content[] =
            (new VerticalBarGraph('api endpoints', $apiEndpoints))
                ->setLegend($apiEndpointsLabels)
                ->addAttributes(['class' => 'svg-half'])
                ->draw();
        $content[] =
            (new VerticalBarGraph('api clients', $apiClients))
                ->setLegend($apiClientsLabels)
                ->addAttributes(['class' => 'svg-half'])
                ->draw();

        $this->data = $content;
    }

    protected function displayMiscData($dataset)
    {
        var_dump($dataset);
    }

    public function draw()
    {
        $content = [
            $this->data
        ];

        $this->setContent($content);

        return $this;
    }
}
