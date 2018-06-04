<?php
namespace LogHero\Client;
require_once __DIR__ . '/APIAccess.php';
require_once __DIR__ . '/LogEvent.php';
require_once __DIR__ . '/LogFlushStrategy.php';


class Client {
    private $logBuffer;
    private $flushStrategy;
    private $maxRecordSizeInBytes;
    private $maxFlushTimeIntervalSeconds;

    public function __construct(LogBuffer $logBuffer, LogFlushStrategy $flushStrategy, $maxRecordSizeInBytes=4000, $maxFlushTimeIntervalSeconds=300) {
        $this->logBuffer = $logBuffer;
        $this->flushStrategy = $flushStrategy;
        $this->maxRecordSizeInBytes = $maxRecordSizeInBytes;
        $this->maxFlushTimeIntervalSeconds = $maxFlushTimeIntervalSeconds;
    }

    public static function create(LogBuffer $logBuffer, LogFlushStrategy $flushStrategy) {
        return new Client($logBuffer, $flushStrategy);
    }

    public function submit($logEvent) {
        $this->logBuffer->push($logEvent);
        if ($this->needsFlush()) {
            $this->flush();
        }
    }

    public function flush() {
        if ($this->logBuffer->sizeInBytes() === 0) {
            return;
        }
        $this->flushStrategy->flush($this->logBuffer);
        // TODO Move to flush strategy
        //$payload = $this->buildPayload($this->logBuffer->dump());
        //$this->send($payload);
    }

    private function buildPayload($logEvents) {
        $rows = array();
        $columns = NULL;
        foreach ($logEvents as $logEvent) {
            try {
                array_push($rows, $logEvent->row());
                if (is_null($columns)) {
                    $columns = $logEvent->columns();
                }
            }
            // TODO: Test exception handling
            catch (\Exception $e) {
                //print($e);
            }
        }
        assert(is_null($columns) == false);
        return array(
            'columns' => $columns,
            'rows' => $rows
        );
    }

    public function needsFlush() {
        $sizeInBytes = $this->logBuffer->sizeInBytes();
        if ($sizeInBytes >= $this->maxRecordSizeInBytes) {
            return true;
        }
//        $firstLogEvent = $this->logBuffer->getFirstLogEvent();
//        if (!$firstLogEvent) {
//            return false;
//        }
//        $currentUnixTimestamp = microtime(true);
//        $currentTimestamp = new \DateTime();
//        $currentTimestamp->setTimestamp($currentUnixTimestamp);
//        $currentTimeIntervalSeconds = $currentTimestamp->getTimestamp() - $firstLogEvent->getTimestamp()->getTimestamp();
//        if ($currentTimeIntervalSeconds >= $this->maxFlushTimeIntervalSeconds) {
//            return true;
//        }
        return false;
    }

    private function send($payload) {
        $this->apiAccess->submitLogPackage(json_encode($payload));
    }

}

