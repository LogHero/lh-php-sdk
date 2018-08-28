<?php
namespace LogHero\Client;


class RedisLogBuffer implements LogBufferInterface {
    private $redisClient;
    private $redisLogBufferKey;
    private $redisLastDumpBufferKey;
    private $numberOfEventsInBuffer;
    private $lastDumpTimestamp;
    private $maxNumberOfEventsInBuffer;
    private $maxDumpTimeIntervalSeconds;

    public function __construct(
        $redisClient,
        $redisOptions,
        $maxNumberOfEventsInBuffer=1000,
        $maxDumpTimeIntervalSeconds=300
    ) {
        $this->redisClient = $redisClient;
        $this->redisLogBufferKey = $redisOptions->getRedisKeyPrefix() . ':logs';
        $this->redisLastDumpBufferKey = $this->redisLogBufferKey . ':last-dump';
        $this->numberOfEventsInBuffer = 0;
        $this->maxNumberOfEventsInBuffer = $maxNumberOfEventsInBuffer;
        $this->maxDumpTimeIntervalSeconds = $maxDumpTimeIntervalSeconds;
    }

    public function push($logEvent) {
        $responses = $this->redisClient
            ->transaction()
            ->lpush($this->redisLogBufferKey, serialize($logEvent))
            ->get($this->redisLastDumpBufferKey)
            ->execute();
        $this->numberOfEventsInBuffer = $responses[0];
        $this->lastDumpTimestamp = (float) $responses[1];
    }

    public function needsDumping() {
        if($this->numberOfEventsInBuffer >= $this->maxNumberOfEventsInBuffer) {
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
}
