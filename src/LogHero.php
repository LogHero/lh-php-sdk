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
        if ($this->logBuffer->needsDumping()) {
            $this->flush();
        }
    }

    public function flush() {
        $this->flushStrategy->flush();
    }
}

