<?php
namespace LogHero\Client;

use LogHero\Client\RedisOptions;


class RedisLogBuffer implements LogBufferInterface {
    private $redisClient;
    private $redisLogBufferKey;
    private $numberOfEventsInBuffer;
    private $maxNumberOfEventsInBuffer;

    public function __construct($redisClient, $redisOptions) {
        $this->redisClient = $redisClient;
        $this->redisLogBufferKey = $redisOptions->getRedisKeyPrefix() .':logs';
        $this->numberOfEventsInBuffer = 0;
        $this->maxNumberOfEventsInBuffer = 5;
    }

    public function push($logEvent) {
        $this->numberOfEventsInBuffer = $this->redisClient->lpush($this->redisLogBufferKey, serialize($logEvent));
    }

    public function needsDumping() {
        return $this->numberOfEventsInBuffer >= $this->maxNumberOfEventsInBuffer;
    }

    public function dump() {
        $responses = $this->redisClient->transaction()
            ->lrange($this->redisLogBufferKey, 0, -1)
            ->del($this->redisLogBufferKey)->execute();
        $logEventsSerialized = $responses[0];
        $logEventsUnserialized = array();
        foreach($logEventsSerialized as $logEvent) {
            $logEventsUnserialized[] = unserialize($logEvent);
        }
        return $logEventsUnserialized;
    }
}
