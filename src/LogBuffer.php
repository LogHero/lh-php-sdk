<?php
namespace LogHero\Client;
require_once __DIR__ . '/LogEvent.php';


interface LogBuffer {
    public function push($logEvent);
    public function sizeInBytes();
    public function dump();
}


// TODO Is not thread safe yet
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
    private $lockFile;
    private $currentLogEvent;

    public function __construct($bufferFileName) {
        $this->fileLocation = $bufferFileName;
        $lockFileLocation = $bufferFileName . '.lock';
        $this->lockFile = fopen($lockFileLocation, 'w');
        chmod($lockFileLocation, 0666);
    }

    public function push($logEvent) {
        file_put_contents($this->fileLocation, serialize($logEvent)."\n", FILE_APPEND | LOCK_EX);
        $this->currentLogEvent = $logEvent;
    }

    public function sizeInBytes() {
        return 300000;


        # TODO: Return actual size in bytes:
        if(!file_exists($this->fileLocation)) {
            return 0;
        }
        return filesize($this->fileLocation);
    }

    public function dump() {
        #TODO Read from buffer file:
        $logEvents = array();
        assert($this->currentLogEvent);
        array_push($logEvents, $this->currentLogEvent);
        return $logEvents;
    }

    private function lock() {
        $timeoutMilliSec = 2000;
        $lockIntervalMilliSec = 10;
        $waitIfLocked = false;
        while($timeoutMilliSec > 0) {
            if(flock($this->lockFile, LOCK_EX | LOCK_NB, $waitIfLocked)) {
                return;
            }
            $timeoutMilliSec -= $lockIntervalMilliSec;
            usleep($lockIntervalMilliSec * 1000);
        }
        throw new \Exception('Cannot acquire lock to write to log file.');
    }

    private function unlock() {
        flock($this->lockFile, LOCK_UN);
    }

}