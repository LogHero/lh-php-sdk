<?php
namespace LogHero\Client;
require_once __DIR__ . '/LogBuffer.php';


class FileLogBuffer implements LogBufferInterface {
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
        file_put_contents($this->fileLocation, serialize($logEvent)."\n", FILE_APPEND | LOCK_EX);
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
                $logEvents[] = unserialize($logEventLine);
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
