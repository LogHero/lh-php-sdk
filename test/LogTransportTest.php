<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/LogBuffer.php';
require_once __DIR__ . '/../src/transport/LogTransport.php';
require_once __DIR__ . '/Util.php';
require_once __DIR__ . '/MicrotimeMock.php';

use PHPUnit\Framework\TestCase;


class LogTransportTest extends TestCase {
    private $logBuffer;
    private $logTransport;
    private $apiAccessStub;

    public function setUp() {
        $GLOBALS['currentTime'] = 1523429300.8000;
        $this->logBuffer = new MemLogBuffer($maxLogEventsInBuffer=5);
        $this->apiAccessStub = $this->createMock(APIAccess::class);
        $this->logTransport = new LogTransport($this->logBuffer, $this->apiAccessStub);
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
        $this->logTransport->flush();
    }

    public function testSubmitLogEventsIfMaxBufferSizeIsReached() {
        $this->apiAccessStub
            ->expects(static::exactly(2))
            ->method('submitLogPackage');
        $batch1 = buildExpectedPayloadForLogEvents(array(
            createLogEvent('/page-1'),
            createLogEvent('/page-2'),
            createLogEvent('/page-3'),
            createLogEvent('/page-4'),
            createLogEvent('/page-5')
        ));
        $batch2 = buildExpectedPayloadForLogEvents(array(
            createLogEvent('/page-6'),
            createLogEvent('/page-7'),
            createLogEvent('/page-8'),
            createLogEvent('/page-9'),
            createLogEvent('/page-10')
        ));
        $this->apiAccessStub
            ->expects($this->at(0))
            ->method('submitLogPackage')
            ->with($this->equalTo($batch1));
        $this->apiAccessStub
            ->expects($this->at(1))
            ->method('submitLogPackage')
            ->with($this->equalTo($batch2));
        for ($x = 0; $x < 11; ++$x) {
            $this->logTransport->submit(createLogEvent('/page-'.($x+1)));
        }
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
        $this->logTransport->flush();
    }

    public function testNoApiHitIfNoValidLogEvents() {
        $invalidLogEvent = createLogEvent('/page-2');
        $invalidLogEvent->setUserAgent(null);
        $this->logBuffer->push($invalidLogEvent);
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->logTransport->flush();
    }

}