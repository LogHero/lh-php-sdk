<?php
namespace LogHero\Client\Test;

use LogHero\Client\RedisLogBuffer;
use LogHero\Client\RedisOptions;
use PHPUnit\Framework\TestCase;


interface RedisClientMockInterface {
    public function lpush($key, $data);
    public function set($key, $data);
    public function transaction();
    public function lrange($key, $start, $stop);
    public function del($key);
    public function get($key);
    public function execute();
}


# TODO ERROR HANDLING!!
class RedisLogBufferTest extends TestCase {
    private $logBuffer;
    private $redisClientMock;
    private $maxNumberOfEventsInBuffer = 3;
    private $maxDumpTimeIntervalSeconds = 4;
    private $microtimeMock;
    private static $currentTime = 1523429300.8000;

    public function setUp() {
        parent::setUp();
        $GLOBALS['currentTime'] = static::$currentTime;
        $this->microtimeMock = createMicrotimeMock();
        $this->microtimeMock->enable();
        $redisOptions = new RedisOptions('REDIS_URL', 'key-prefix');
        $this->redisClientMock = $this->createMock(RedisClientMockInterface::class);
        $this->logBuffer = new RedisLogBuffer(
            $this->redisClientMock,
            $redisOptions,
            $this->maxNumberOfEventsInBuffer,
            $this->maxDumpTimeIntervalSeconds
        );
    }

    public function tearDown() {
        parent::tearDown();
        $this->microtimeMock->disable();
    }

    public function testPushLogEvent() {
        $logEvent = createLogEvent('/page');
        $this->expectPush($logEvent, 1, static::$currentTime);
        $this->logBuffer->push($logEvent);
    }

    public function testNeedsNoDumpingIfWithinMaxBufferSize(){
        $logEvent = createLogEvent('/page');
        $this->expectPush($logEvent, 1, static::$currentTime);
        $this->logBuffer->push($logEvent);
        static::assertFalse($this->logBuffer->needsDumping());
    }

    public function testNeedsDumpingIfExceedsMaxBufferSize() {
        $logEvent = createLogEvent('/page');
        $this->expectPush($logEvent, 3, static::$currentTime);
        $this->logBuffer->push($logEvent);
        static::assertTrue($this->logBuffer->needsDumping());
    }

    public function testNeedsDumpingIfNoDumpTimestampAvailable() {
        $logEvent = createLogEvent('/page');
        $this->expectPush($logEvent, 1, null);
        $this->logBuffer->push($logEvent);
        static::assertTrue($this->logBuffer->needsDumping());
    }

    public function testNeedsDumpingIfMaxDumpTimeIntervalReached() {
        $logEvent = createLogEvent('/page');
        $nowMinus5Seconds = static::$currentTime - 5;
        $this->expectPush($logEvent, 1, $nowMinus5Seconds);
        $this->logBuffer->push($logEvent);
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
            1,
            $logEventsSerialized
        );
        $this->redisClientMock
            ->expects($this->once())
            ->method('del')
            ->with($this->equalTo('key-prefix:logs'))
            ->willReturn($this->redisClientMock);
        $this->redisClientMock
            ->expects($this->once())
            ->method('set')
            ->with($this->equalTo('key-prefix:logs:last-dump'), $this->equalTo(1523429300.8000))
            ->willReturn($this->redisClientMock);
        $this->redisClientMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn($responses);
        $logEvents = $this->logBuffer->dump();
        static::assertEquals('/page-1', $logEvents[0]->row()[3]);
        static::assertEquals('/page-2', $logEvents[1]->row()[3]);
    }

    private function expectPush($logEvent, $lpushReturnValue, $lastDumpTimestamp) {
        if ($lastDumpTimestamp) {
            $lastDumpTimestamp = (string) $lastDumpTimestamp;
        }
        $this->redisClientMock
            ->expects($this->once())
            ->method('transaction')
            ->willReturn($this->redisClientMock);
        $this->redisClientMock
            ->expects($this->once())
            ->method('lpush')
            ->with($this->equalTo('key-prefix:logs'), $this->equalTo(serialize($logEvent)))
            ->willReturn($this->redisClientMock);
        $this->redisClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('key-prefix:logs:last-dump'))
            ->willReturn($this->redisClientMock);
        $responses = array(
            $lpushReturnValue,
            $lastDumpTimestamp
        );
        $this->redisClientMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn($responses);
    }

}
