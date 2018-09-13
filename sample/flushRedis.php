#!/usr/bin/php -q
<?php
require_once __DIR__ . '/../autoload.php';

use LogHero\Client\APISettings;
use LogHero\Client\APIAccess;
use LogHero\Client\RedisOptions;
use LogHero\Client\RedisLogBuffer;
use LogHero\Client\LogTransport;
use Predis\Client;


$apiKey = 'YOUR_API_KEY';
$clientId = 'Redis-Flush-Sample';
$apiSettings = new APISettings($apiKey);
$redisUrl = getenv('REDIS_URL');
$redisKeyPrefix = 'io.loghero:wp:' . $apiKey;
$redisOptions = new RedisOptions(
    $redisUrl,
    $redisKeyPrefix
);
$redisClient = new Client($redisOptions->getRedisUrl());
$logBuffer = new RedisLogBuffer($redisClient, $redisOptions);
$apiAccess = new APIAccess($clientId, $apiSettings);
$logTransport = new LogTransport($logBuffer, $apiAccess);

if ($logBuffer->needsDumping()) {
    $logTransport->flush();
}
