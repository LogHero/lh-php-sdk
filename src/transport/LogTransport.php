<?php
namespace LogHero\Client;


class LogTransport extends DisabledLogTransport  {
    protected $apiAccess;
    protected $maxLogEventsPerBatch;

    public function __construct(
        LogBufferInterface $logBuffer,
        APIAccessInterface $apiAccess,
        $maxLogEventsPerBatch=1000
    ) {
        parent::__construct($logBuffer);
        $this->apiAccess = $apiAccess;
        $this->maxLogEventsPerBatch = $maxLogEventsPerBatch;
    }

    public function flush() {
        $payloadBatches = $this->buildPayload($this->logBuffer->dump());
        foreach ($payloadBatches as $payload) {
            $this->sendPayloadBatch($payload);
        }
    }

    private function sendPayloadBatch($payload) {
        if ($payload['columns'] === null || count($payload['rows']) === 0) {
            return;
        }
        $this->send($payload);
    }

    private function buildPayload(array $logEvents) {
        $payloadBatches = array();
        $rows = array();
        $columns = null;
        foreach ($logEvents as $logEvent) {
            try {
                if ($columns === null) {
                    $columns = $logEvent->columns();
                }
                $rows[] = $logEvent->row();
                if (count($rows) >= $this->maxLogEventsPerBatch) {
                    $payloadBatches[] = array(
                        'columns' => $columns,
                        'rows' => $rows
                    );
                    $rows = array();
                }
            }
            catch (\Exception $e) {
            }
        }
        $payloadBatches[] = array(
            'columns' => $columns,
            'rows' => $rows
        );
        return $payloadBatches;
    }

    private function send($payload) {
        $this->apiAccess->submitLogPackage(json_encode($payload));
    }
}