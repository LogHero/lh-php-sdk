<?php

interface LogBuffer {
    public function push($logEvent);
    public function sizeInBytes();
    public function dump();
}

class MemLogBuffer implements LogBuffer {
    private $logEvents = array();
    private $estimatedLogEventSizeInBytes;

    public function __construct($estimatedLogEventSizeInBytes=150) {
        $this->estimatedLogEventSizeInBytes = $estimatedLogEventSizeInBytes;
    }

    public function push($logEvent) {
        array_push($this->logEvents, $logEvent);
    }

    public function sizeInBytes() {
        return count($this->logEvents) * $this->estimatedLogEventSizeInBytes;
    }

    public function dump() {
        $dumpedLogEvents = $this->logEvents;
        $this->logEvents = array();
        return $dumpedLogEvents;
    }

}

class FileLogBuffer implements LogBuffer {
    private $fileLocation;

    public function __construct($fileLocation) {
        $this->fileLocation = $fileLocation;
    }

    public function push($logEvent) {
        file_put_contents($this->fileLocation, serialize($logEvent->row())."\n", FILE_APPEND | LOCK_EX);
    }

    public function sizeInBytes() {
        if(!file_exists($this->fileLocation)) {
            return 0;
        }
        return filesize($this->fileLocation);
    }

    public function dump() {

    }

}