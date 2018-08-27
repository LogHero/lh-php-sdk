<?php
namespace LogHero\Client;

use Predis\Client;


class RedisLogBuffer implements LogBufferInterface {
    private $redisClient;
    private $redisLogBufferKey;
    private $numberOfEventsInBuffer;
    private $maxNumberOfEventsInBuffer;

    public function __construct(
    ) {
        $this->redisClient = new \Predis\Client('redis://192.168.99.100:6379');
        $this->redisLogBufferKey = 'loghero:logs';
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
