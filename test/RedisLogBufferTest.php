<?php
namespace LogHero\Client\Test;

use LogHero\Client\RedisLogBuffer;
use LogHero\Client\RedisOptions;
use PHPUnit\Framework\TestCase;
use Predis\Client;


interface RedisClientMockInterface {
    public function lpush($key, $data);
    public function transaction();
    public function lrange($key, $start, $stop);
    public function del($key);
    public function execute();
}


# TODO ERROR HANDLING!!
class RedisLogBufferTest extends TestCase {
    private $logBuffer;
    private $redisClientMock;
    private $maxNumberOfEventsInBuffer = 3;

    public function setUp() {
        parent::setUp();
        $redisOptions = new RedisOptions('REDIS_URL', 'key-prefix');
        $this->redisClientMock = $this->createMock(RedisClientMockInterface::class);
        $this->logBuffer = new RedisLogBuffer($this->redisClientMock, $redisOptions, $this->maxNumberOfEventsInBuffer);
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

    public function testNeedsNoDumpingIfWithinMaxBufferSize(){
        $this->redisClientMock
            ->expects($this->once())
            ->method('lpush')
            ->willReturn(1);
        $this->logBuffer->push(createLogEvent('/page'));
        static::assertFalse($this->logBuffer->needsDumping());
    }

    public function testNeedsDumpingIfExceedsMaxBufferSize() {
        $this->redisClientMock
            ->expects($this->once())
            ->method('lpush')
            ->willReturn(3);
        $this->logBuffer->push(createLogEvent('/page'));
        static::assertTrue($this->logBuffer->needsDumping());
    }

    public function testDumpReturnsLogEventsAndClearsBuffer() {
        $this->redisClientMock
            ->expects($this->once())
            ->method('transaction')
            ->willReturn($this->redisClientMock);
        $this->redisClientMock
            ->expects($this->once())
            ->method('lrange')
            ->with($this->equalTo('key-prefix:logs'), $this->equalTo(0), $this->equalTo(-1))
            ->willReturn($this->redisClientMock);
        $logEventsSerialized = array(
            serialize(createLogEvent('/page-1')),
            serialize(createLogEvent('/page-2'))
        );
        $responses = array(
            $logEventsSerialized
        );
        $this->redisClientMock
            ->expects($this->once())
            ->method('del')
            ->with($this->equalTo('key-prefix:logs'))
            ->willReturn($this->redisClientMock);
        $this->redisClientMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn($responses);
        $logEvents = $this->logBuffer->dump();
        static::assertEquals('/page-1', $logEvents[0]->row()[3]);
        static::assertEquals('/page-2', $logEvents[1]->row()[3]);
    }

}
