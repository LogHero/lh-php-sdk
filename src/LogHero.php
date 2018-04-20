<?php
require_once __DIR__ . '/APIAccess.php';
require_once __DIR__ . '/LogEvent.php';


class LHClient {
    private $apiAccess;
    private $logEventsPerRecord = 25;
    private $logEvents = array();

    public function __construct($apiAccess, $logEventsPerRecord=25) {
        $this->apiAccess = $apiAccess;
        $this->logEventsPerRecord = $logEventsPerRecord;
    }

    public static function create($apiKey, $clientId, $logEndpoint='https://development.loghero.io/logs/') {
        return new LHClient(new APIAccessCurl($apiKey, $clientId, $logEndpoint));
    }

    public function submit($logEvent) {
        array_push($this->logEvents, $logEvent);
        if (count($this->logEvents) >= $this->logEventsPerRecord) {
            $this->flush();
        }
    }

    public function flush() {
        if (count($this->logEvents) == 0) {
            return;
        }
        $payload = $this->buildPayload();
        $this->send($payload);
        $this->logEvents = array();
    }

    private function buildPayload() {
        $rows = array();
        $columns = NULL;
        foreach ($this->logEvents as $logEvent) {
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

