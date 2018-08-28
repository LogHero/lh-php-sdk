<?php
namespace LogHero\Client\Test;

use LogHero\Client\RedisLogBuffer;
use LogHero\Client\RedisOptions;
use PHPUnit\Framework\TestCase;
use Predis\Client;


interface RedisClientMockInterface {
    public function lpush($key, $data);
}


class RedisLogBufferTest extends TestCase {
    private $logBuffer;
    private $redisClientMock;

    public function setUp() {
        parent::setUp();
        $redisOptions = new RedisOptions('REDIS_URL', 'key-prefix');
        $this->redisClientMock = $this->createMock(RedisClientMockInterface::class);
        $this->logBuffer = new RedisLogBuffer($this->redisClientMock, $redisOptions);
    }

    public function testPushLogEvent() {
        $logEvent = createLogEvent('/page');
        $this->redisClientMock
            ->expects($this->once())
            ->method('lpush')
            ->with($this->equalTo('key-prefix:logs'), $this->equalTo(serialize($logEvent)))
            ->willReturn(1);
        $this->logBuffer->push($logEvent);
    }

}
