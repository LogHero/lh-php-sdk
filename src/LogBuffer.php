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
    private $firstLogEvent = null;

    // TODO: DEBUGGING ONLY
    private $failLogFile;

    public function __construct($bufferFileName) {
        $this->fileLocation = $bufferFileName;
        $lockFileLocation = $bufferFileName . '.lock';
        $this->lockFile = fopen($lockFileLocation, 'w');
        chmod($lockFileLocation, 0666);

        // TODO: DEBUGGING ONLY
        $this->failLogFile = $this->fileLocation . '.fail';
    }

    public function push($logEvent) {
        $this->lock(serialize($logEvent));
        $handle = fopen($this->fileLocation, 'c+');
        if (!$handle) {
            // TODO Not tested yet
            print('ERROR WRITING TO LOGHERO BUFFER FILE');
            file_put_contents($this->failLogFile, 'WRITE ERROR: '.serialize($logEvent)."\n", FILE_APPEND | LOCK_EX);
            $this->unlock();
            return;
        }
        $firstLogEventLine = fgets($handle);
        if ($firstLogEventLine) {
            $this->firstLogEvent = unserialize($firstLogEventLine);
        }
        else {
            $this->firstLogEvent = $logEvent;
        }
        fseek($handle, 0, SEEK_END);
        fwrite($handle, serialize($logEvent)."\n");
        fclose($handle);
        chmod($this->fileLocation, 0666);
        $this->unlock();
    }

    public function sizeInBytes() {
        if(!file_exists($this->fileLocation)) {
            return 0;
        }
        return filesize($this->fileLocation);
    }

    public function getFirstLogEvent() {
        return $this->firstLogEvent;
    }

    public function dump() {
        $this->lock('DUMP');
        $logEvents = array();
        try {
            $handle = fopen($this->fileLocation, 'r+');
            if ($handle) {
                while (($logEventLine = fgets($handle)) !== false) {
                    array_push($logEvents, unserialize($logEventLine));
                }
                ftruncate($handle, 0);
                fclose($handle);
            }
        }
        catch(\Exception $e) {
        }
        $this->unlock();
        return $logEvents;
    }

    private function lock($lockId) {
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

        // TODO: DEBUGGING ONLY
        file_put_contents($this->failLogFile, $lockId."\n", FILE_APPEND | LOCK_EX);
        chmod($this->failLogFile, 0666);

        throw new \Exception('Cannot acquire lock to write to log file.');
    }

    private function unlock() {
        flock($this->lockFile, LOCK_UN);
    }

}