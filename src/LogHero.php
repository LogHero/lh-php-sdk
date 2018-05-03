<?php
namespace LogHero\Client;
require_once __DIR__ . '/APIAccess.php';
require_once __DIR__ . '/LogEvent.php';


class Client {
    private $apiAccess;
    private $logBuffer;
    private $maxRecordSizeInBytes;
    private $maxFlushTimeIntervalSeconds;

    public function __construct($apiAccess, $logBuffer, $maxRecordSizeInBytes=3000, $maxFlushTimeIntervalSeconds=300) {
        $this->apiAccess = $apiAccess;
        $this->logBuffer = $logBuffer;
        $this->maxRecordSizeInBytes = $maxRecordSizeInBytes;
        $this->maxFlushTimeIntervalSeconds = $maxFlushTimeIntervalSeconds;
    }

    public static function create($apiKey, $clientId, $logBuffer, $logEndpoint='https://development.loghero.io/logs/') {
        return new Client(new APIAccessCurl($apiKey, $clientId, $logEndpoint), $logBuffer);
    }

    public function submit($logEvent) {
        $this->logBuffer->push($logEvent);
        if ($this->needsFlush()) {
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

    private function needsFlush() {
        if ($this->logBuffer->sizeInBytes() >= $this->maxRecordSizeInBytes) {
            return true;
        }
        $currentUnixTimestamp = microtime();
        $currentTimestamp = new \DateTime();
        $currentTimestamp->setTimestamp($currentUnixTimestamp);
        $firstLogEvent = $this->logBuffer->getFirstLogEvent();
        if (!$firstLogEvent) {
            return false;
        }
        $currentTimeIntervalSeconds = $currentTimestamp->getTimestamp() - $firstLogEvent->getTimestamp()->getTimestamp();
        if ($currentTimeIntervalSeconds >= $this->maxFlushTimeIntervalSeconds) {
            return true;
        }
        return false;
    }

    private function send($payload) {
        $this->apiAccess->submitLogPackage(json_encode($payload));
    }

}

