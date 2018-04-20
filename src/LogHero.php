<?php
require_once __DIR__ . '/APIAccess.php';
require_once __DIR__ . '/LogEvent.php';


class LHClient {
    private $apiAccess;
    private $logBuffer;
    private $maxRecordSizeInBytes;

    public function __construct($apiAccess, $logBuffer, $maxRecordSizeInBytes=3000) {
        $this->apiAccess = $apiAccess;
        $this->logBuffer = $logBuffer;
        $this->maxRecordSizeInBytes = $maxRecordSizeInBytes;
    }

    public static function create($apiKey, $clientId, $logBuffer, $logEndpoint='https://development.loghero.io/logs/') {
        return new LHClient(new APIAccessCurl($apiKey, $clientId, $logEndpoint), $logBuffer);
    }

    public function submit($logEvent) {
        $this->logBuffer->push($logEvent);
        if ($this->logBuffer->sizeInBytes() >= $this->maxRecordSizeInBytes) {
            $this->flush();
        }
    }

    public function flush() {
        if ($this->logBuffer->sizeInBytes() == 0) {
            return;
        }
        $payload = $this->buildPayload($this->logBuffer->dump());
        $this->send($payload);
        $this->logEvents = array();
    }

    private function buildPayload($logEvents) {
        $rows = array();
        $columns = NULL;
        foreach ($logEvents as $logEvent) {
            array_push($rows, $logEvent->row());
            if (is_null($columns)) {
                $columns = $logEvent->columns();
            }
        }
        assert(is_null($columns) == false);
        return array(
            'columns' => $columns,
            'rows' => $rows
        );
    }

    private function send($payload) {
        $this->apiAccess->submitLogPackage(json_encode($payload));
    }

}

