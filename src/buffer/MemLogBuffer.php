<?php
namespace LogHero\Client;


// TODO Is not thread safe yet
class MemLogBuffer implements LogBufferInterface {
    private $logEvents = array();
    private $maxLogEvents;

    public function __construct($maxLogEvents=10) {
        $this->maxLogEvents = $maxLogEvents;
    }

    public function push($logEvent) {
        $this->logEvents[] = $logEvent;
    }

    public function needsDumping() {
        return count($this->logEvents) >= $this->maxLogEvents;
    }

    public function dump() {
        $dumpedLogEvents = $this->logEvents;
        $this->logEvents = array();
        return $dumpedLogEvents;
    }
}
