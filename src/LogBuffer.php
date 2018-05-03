<?php
namespace LogHero\Client;
require_once __DIR__ . '/LogEvent.php';


interface LogBuffer {
    public function push($logEvent);
    public function sizeInBytes();
    public function getFirstLogEvent();
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

    public function getFirstLogEvent() {
        if (count($this->logEvents) == 0) {
            return null;
        }
        return $this->logEvents[0];
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
        $this->lockFile = fopen($bufferFileName . '.lock', 'w');
    }

    public function push($logEvent) {
        $this->lock();
        umask(0111);
        if (!file_put_contents($this->fileLocation, serialize($logEvent)."\n", FILE_APPEND)) {
            // TODO Raise exception here and add test case:
            print('ERROR WRITING TO LOGHERO BUFFER FILE');
        }
        $this->unlock();
    }

    public function sizeInBytes() {
        if(!file_exists($this->fileLocation)) {
            return 0;
        }
        return filesize($this->fileLocation);
    }

    // TODO: Get first log event in push method to avoid accessing the log file twice
    public function getFirstLogEvent() {
        $this->lock();
        $firsLogEvent = null;
        if(file_exists($this->fileLocation)) {
            $handle = fopen($this->fileLocation, 'r');
            if ($handle) {
                $logEventLine = fgets($handle);
                if ($logEventLine) {
                    $firsLogEvent = unserialize($logEventLine);
                }
            }
        }
        $this->unlock();
        return $firsLogEvent;
    }

    public function dump() {
        $this->lock();
        $logEvents = array();
        if(file_exists($this->fileLocation)) {
            $handle = fopen($this->fileLocation, 'r');
            if ($handle) {
                while (($logEventLine = fgets($handle)) !== false) {
                    array_push($logEvents, unserialize($logEventLine));
                }
                fclose($handle);
            }
            unlink($this->fileLocation);
        }
        $this->unlock();
        return $logEvents;
    }

    private function lock() {
        $waitIfLocked = true;
        $locked = flock($this->lockFile, LOCK_EX, $waitIfLocked);
    }

    private function unlock() {
        flock($this->lockFile, LOCK_UN);
    }

}