<?php
namespace LogHero\Client;


class FileLogBuffer implements LogBufferInterface {
    private $fileLocation;
    private $lastDumpTimestampFileLocation;
    private $flushBufferFileSizeInBytes;
    private $maxDumpTimeIntervalSeconds;
    private $maxPushBufferFileSizeInBytes;

    public function __construct(
        $bufferFileName,
        $flushBufferFileSizeInBytes=100000,
        $maxDumpTimeIntervalSeconds=300,
        $maxPushBufferFileSizeInBytes=5000000
    ) {
        if ($maxPushBufferFileSizeInBytes <= $flushBufferFileSizeInBytes) {
            throw new \Exception(
                'Inconsistent configuration: $maxPushBufferFileSizeInBytes is smaller than $flushBufferFileSizeInBytes: '
                . $maxPushBufferFileSizeInBytes . ' <= ' . $flushBufferFileSizeInBytes
            );
        }
        $this->fileLocation = $bufferFileName;
        FileStorage::verifyWriteAccess($this->fileLocation);
        $this->maxDumpTimeIntervalSeconds = $maxDumpTimeIntervalSeconds;
        $this->lastDumpTimestampFileLocation = str_replace('.txt', '', $bufferFileName) . '.last-dump.timestamp';
        $this->flushBufferFileSizeInBytes = $flushBufferFileSizeInBytes;
        $this->maxPushBufferFileSizeInBytes = $maxPushBufferFileSizeInBytes;
    }

    public function push($logEvent) {
        if ($this->sizeInBytes() >= $this->maxPushBufferFileSizeInBytes) {
            throw new BufferSizeExceededException(
                'Maximum buffer size reached (' . $this->maxPushBufferFileSizeInBytes . ' Bytes)! Pushing further log events is prohibited to avoid running out of disk space'
            );
        }
        file_put_contents($this->fileLocation, serialize($logEvent)."\n", FILE_APPEND | LOCK_EX);
        chmod($this->fileLocation, 0666);
    }

    public function needsDumping() {
        $bufferFileSizeInBytes = $this->sizeInBytes();
        if ($bufferFileSizeInBytes === 0) {
            return false;
        }
        if ($bufferFileSizeInBytes >= $this->flushBufferFileSizeInBytes) {
            return true;
        }
        if (!file_exists($this->lastDumpTimestampFileLocation)) {
            return true;
        }
        $lastDumpTimestamp = filemtime ($this->lastDumpTimestampFileLocation);
        $currentTimestamp = microtime(true);
        $nextDumpTimestamp = $lastDumpTimestamp + $this->maxDumpTimeIntervalSeconds;
        return $currentTimestamp >= $nextDumpTimestamp;
    }

    public function dump() {
        $logEvents = array();
        if (!file_exists($this->fileLocation)) {
            return $logEvents;
        }
        $fp = fopen($this->fileLocation, 'r+');
        if (flock($fp, LOCK_EX)) {
            file_put_contents ($this->lastDumpTimestampFileLocation, 'loghero.io');
            chmod($this->lastDumpTimestampFileLocation, 0666);
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
