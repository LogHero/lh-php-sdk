<?php
namespace LogHero\Client;


class RedisOptions {
    private $redisUrl;
    private $redisKeyPrefix;

    public function __construct($redisUrl, $redisKeyPrefix) {
        $this->redisUrl = $redisUrl;
        $this->redisKeyPrefix = $redisKeyPrefix;
    }

    public function getRedisUrl() {
        return $this->redisUrl;
    }

    public function getRedisKeyPrefix() {
        return $this->redisKeyPrefix;
    }

}