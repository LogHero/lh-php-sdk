<?php
namespace LogHero\Client;


class RedisOptions {
    private $redisUrl;
    private $redisKeyPrefix;
    public static $defaultRedisKeyPredix = 'io:loghero:wp';

    public function __construct($redisUrl, $redisKeyPrefix = null) {
        $this->redisUrl = $redisUrl;
        $this->redisKeyPrefix = $redisKeyPrefix ? $redisKeyPrefix : static::$defaultRedisKeyPredix;
    }

    public function getRedisUrl() {
        return $this->redisUrl;
    }

    public function getRedisKeyPredix() {
        return $this->redisKeyPrefix;
    }

}