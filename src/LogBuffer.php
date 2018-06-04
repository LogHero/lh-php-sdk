<?php
namespace LogHero\Client;
require_once __DIR__ . '/LogEvent.php';


interface LogBuffer {
    public function push($logEvent);
    public function needsDumping();
    public function dump();
}


// TODO Is not thread safe yet
class MemLogBuffer implements LogBuffer {
    private $logEvents = array();
    private $maxLogEvents;

    public function __construct($maxLogEvents=10) {
        $this->maxLogEvents = $maxLogEvents;
    }

    public function push($logEvent) {
        array_push($this->logEvents, $logEvent);
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

class FileLogBuffer implements LogBuffer {
    private $fileLocation;
    private $lockFile;
    private $maxBufferFileSizeInBytes;

    public function __construct($bufferFileName, $maxBufferFileSizeInBytes=15000) {
        $this->fileLocation = $bufferFileName;
        $lockFileLocation = $bufferFileName . '.lock';
        $this->lockFile = fopen($lockFileLocation, 'w');
        chmod($lockFileLocation, 0666);
        $this->maxBufferFileSizeInBytes = $maxBufferFileSizeInBytes;
    }

    public function push($logEvent) {
        $result = file_put_contents($this->fileLocation, serialize($logEvent)."\n", FILE_APPEND | LOCK_EX);
        chmod($this->fileLocation, 0666);
    }

    public function needsDumping() {
        return $this->sizeInBytes() >= $this->maxBufferFileSizeInBytes;
    }

    public function dump() {
        $logEvents = array();
        if (!file_exists($this->fileLocation)) {
           return $logEvents;
        }
        $fp = fopen($this->fileLocation, 'r+');
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

    private function sizeInBytes() {
        if(!file_exists($this->fileLocation)) {
            return 0;
        }
        return filesize($this->fileLocation);
    }
}