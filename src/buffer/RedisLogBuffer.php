<?php
namespace LogHero\Client;


class RedisLogBuffer implements LogBufferInterface {
    private $redisClient;
    private $redisLogBufferKey;
    private $redisLastDumpBufferKey;
    private $numberOfEventsInBuffer;
    private $lastDumpTimestamp;
    private $maxEventsInBufferForFlush;
    private $maxDumpTimeIntervalSeconds;
    private $maxEventsInBufferForTrim;

    public function __construct(
        $redisClient,
        RedisOptions $redisOptions,
        $maxEventsInBufferForFlush=1000,
        $maxDumpTimeIntervalSeconds=300,
        $maxEventsInBufferForTrim=1000000
    ) {
        if ($maxEventsInBufferForTrim <= $maxEventsInBufferForFlush) {
            throw new \Exception(
                'Inconsistent configuration: $maxEventsInBufferForTrim is smaller than $maxEventsInBufferForFlush: '
                . $maxEventsInBufferForTrim . ' <= ' . $maxEventsInBufferForFlush
            );
        }
        $this->redisClient = $redisClient;
        $this->redisLogBufferKey = $redisOptions->getRedisKeyPrefix() . ':logs';
        $this->redisLastDumpBufferKey = $this->redisLogBufferKey . ':last-dump';
        $this->maxEventsInBufferForFlush = $maxEventsInBufferForFlush;
        $this->maxDumpTimeIntervalSeconds = $maxDumpTimeIntervalSeconds;
        $this->maxEventsInBufferForTrim = $maxEventsInBufferForTrim;
    }

    public function push($logEvent) {
        $responses = $this->redisClient
            ->transaction()
            ->rpush($this->redisLogBufferKey, serialize($logEvent))
            ->get($this->redisLastDumpBufferKey)
            ->execute();
        $this->numberOfEventsInBuffer = $responses[0];
        $this->lastDumpTimestamp = (float) $responses[1];
        if ($this->numberOfEventsInBuffer > $this->maxEventsInBufferForTrim) {
            $numberOfElementsToTrim = $this->numberOfEventsInBuffer - $this->maxEventsInBufferForTrim;
            $this->redisClient->ltrim($this->redisLogBufferKey, $numberOfElementsToTrim, -1);
        }
    }

    public function needsDumping() {
        if ($this->numberOfEventsInBuffer === null) {
            $this->refreshDumpStatus();
        }
        if($this->numberOfEventsInBuffer >= $this->maxEventsInBufferForFlush) {
            return true;
        }
        if ($this->lastDumpTimestamp === null) {
            return true;
        }
        $currentTimestamp = microtime(true);
        $nextDumpTimestamp = $this->lastDumpTimestamp + $this->maxDumpTimeIntervalSeconds;
        return $currentTimestamp >= $nextDumpTimestamp;
    }

    public function dump() {
        $unixTimestamp = microtime(true);
        $responses = $this->redisClient
            ->transaction()
            ->set($this->redisLastDumpBufferKey, $unixTimestamp)
            ->lrange($this->redisLogBufferKey, 0, -1)
            ->del($this->redisLogBufferKey)
            ->execute();
        $logEventsSerialized = $responses[1];
        $logEventsUnserialized = array();
        foreach($logEventsSerialized as $logEvent) {
            $logEventsUnserialized[] = unserialize($logEvent);
        }
        return $logEventsUnserialized;
    }

    private function refreshDumpStatus() {
        $responses = $this->redisClient
            ->transaction()
            ->llen($this->redisLogBufferKey)
            ->get($this->redisLastDumpBufferKey)
            ->execute();
        $this->numberOfEventsInBuffer = $responses[0];
        $this->lastDumpTimestamp = (float) $responses[1];
    }
}
