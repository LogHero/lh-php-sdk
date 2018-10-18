<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\MemLogBuffer;
use LogHero\Client\APIAccess;
use LogHero\Client\LogTransport;
use LogHero\Client\DisabledLogTransport;


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

    public function testSubmitLogEventsInBatches() {
        $logTransport = new LogTransport($this->logBuffer, $this->apiAccessStub, 3);
        $this->apiAccessStub
            ->expects(static::exactly(2))
            ->method('submitLogPackage');
        $batch1 = buildExpectedPayloadForLogEvents(array(
            createLogEvent('/page-1'),
            createLogEvent('/page-2'),
            createLogEvent('/page-3')
        ));
        $batch2 = buildExpectedPayloadForLogEvents(array(
            createLogEvent('/page-4'),
            createLogEvent('/page-5')
        ));
        $this->apiAccessStub
            ->expects($this->at(0))
            ->method('submitLogPackage')
            ->with($this->equalTo($batch1));
        $this->apiAccessStub
            ->expects($this->at(1))
            ->method('submitLogPackage')
            ->with($this->equalTo($batch2));
        for ($x = 0; $x < 5; ++$x) {
            $this->logBuffer->push(createLogEvent('/page-'.($x+1)));
        }
        $logTransport->flush();
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

    public function testNoSubmitIfFlushDisabled() {
        $logTransport = new DisabledLogTransport($this->logBuffer, $this->apiAccessStub);
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $logTransport->flush();
    }

}