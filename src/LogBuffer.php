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

    public function __construct($bufferFileName) {
        $this->fileLocation = $bufferFileName;
        $lockFileLocation = $bufferFileName . '.lock';
        $this->lockFile = fopen($lockFileLocation, 'w');
        chmod($lockFileLocation, 0666);
    }

    public function push($logEvent) {
        $result = file_put_contents($this->fileLocation, serialize($logEvent)."\n", FILE_APPEND | LOCK_EX);
        chmod($this->fileLocation, 0666);
    }

    public function sizeInBytes() {
        if(!file_exists($this->fileLocation)) {
            return 0;
        }
        return filesize($this->fileLocation);
    }

    public function dump() {
        $logEvents = array();
        $fp = fopen($this->fileLocation, "r+");
        if (flock($fp, LOCK_EX)) {
            while (($logEventLine = fgets($fp)) !== false) {
                array_push($logEvents, unserialize($logEventLine));
            }
            ftruncate($fp, 0);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
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