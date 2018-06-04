<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/LogBuffer.php';
require_once __DIR__ . '/../src/LogFlushStrategySync.php';
require_once __DIR__ . '/Util.php';
require_once __DIR__ . '/MicrotimeMock.php';

use PHPUnit\Framework\TestCase;


class LogFlushStrategySyncTest extends TestCase {
    private $logBuffer;
    private $flushStrategy;
    private $apiAccessStub;

    public function setUp() {
        $GLOBALS['currentTime'] = 1523429300.8000;
        $this->logBuffer = new MemLogBuffer();
        $this->apiAccessStub = $this->createMock(APIAccess::class);
        $this->flushStrategy = new LogFlushStrategySync($this->logBuffer, $this->apiAccessStub);
    }

    public function testDumpLogBufferAndSendLogEventsToApi() {
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->logBuffer->push(createLogEvent('/page-3'));
        $this->apiAccessStub
            ->expects($this->once())
            ->method('submitLogPackage')
            ->with($this->equalTo(buildExpectedPayloadForLogEvents(array(
                createLogEvent('/page-1'),
                createLogEvent('/page-2'),
                createLogEvent('/page-3')
            ))));
        $this->flushStrategy->flush();
    }

    public function testSkipInvalidLogEvents() {
        $this->logBuffer->push(createLogEvent('/page-1'));
        $invalidLogEvent = createLogEvent('/page-2');
        $invalidLogEvent->setUserAgent(null);
        $this->logBuffer->push($invalidLogEvent);
        $this->logBuffer->push(createLogEvent('/page-3'));
        $this->apiAccessStub
            ->expects($this->once())
            ->method('submitLogPackage')
            ->with($this->equalTo(buildExpectedPayloadForLogEvents(array(
                createLogEvent('/page-1'),
                createLogEvent('/page-3')
            ))));
        $this->flushStrategy->flush();
    }

}