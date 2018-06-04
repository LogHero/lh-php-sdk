<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/LogHero.php';
require_once __DIR__ . '/../src/LogBuffer.php';
require_once __DIR__ . '/../src/LogFlushStrategy.php';
require_once __DIR__ . '/MicrotimeMock.php';


use PHPUnit\Framework\TestCase;


class TestLogFlushStrategy implements LogFlushStrategy {
    public $logsReceived = array();
    public $logBuffer;

    public function __construct(LogBuffer $logBuffer) {
        $this->logBuffer = $logBuffer;
    }

    public function flush() {
        $logs = $this->logBuffer->dump();
        $this->logsReceived = array_merge($this->logsReceived, $logs);
    }
}


class ClientTest extends TestCase {
    private $flushStrategy;
    private $logHeroClient;
    private $maxRecordSizeInBytes = 500;
    private $maxTimeIntervalSeconds = 150;

    public function setUp()
    {
        $GLOBALS['currentTime'] = 1523429300.8000;
        $logBuffer = new MemLogBuffer(100);
        $this->flushStrategy = new TestLogFlushStrategy($logBuffer);
        $this->logHeroClient = new Client(
            $logBuffer,
            $this->flushStrategy,
            $this->maxRecordSizeInBytes,
            $this->maxTimeIntervalSeconds
        );
    }

    public function testSubmitNothingIfNoLogsRecorded() {
        $this->logHeroClient->flush();
        static::assertCount(0, $this->flushStrategy->logsReceived);
    }

    public function testSubmitLogEventToApi() {
        $this->logHeroClient->submit($this->createLogEvent());
        static::assertCount(0, $this->flushStrategy->logsReceived);
        $this->logHeroClient->flush();
        static::assertCount(1, $this->flushStrategy->logsReceived);
    }

    public function testSubmitLogEventsIfRecordSizeIsReached() {
        for ($x = 0; $x < 4; ++$x) {
            $this->logHeroClient->submit($this->createLogEvent());
        }
        static::assertCount(0, $this->flushStrategy->logsReceived);
        $this->logHeroClient->submit($this->createLogEvent());
        static::assertCount(5, $this->flushStrategy->logsReceived);
        for ($x = 0; $x < 5; ++$x) {
            $this->logHeroClient->submit($this->createLogEvent());
        }
        static::assertCount(10, $this->flushStrategy->logsReceived);
    }

//    public function testSubmitLogEventsIfMaximumTimeIntervalIsReached() {
//        $this->apiAccessStub
//            ->expects($this->once())
//            ->method('submitLogPackage')
//            ->with($this->equalTo($this->buildExpectedPayload($this->createLogEventRows(3))));
//        $this->logHeroClient->submit($this->createLogEvent());
//        $timePassedSeconds = 120;
//        $GLOBALS['currentTime'] += $timePassedSeconds;
//        $this->logHeroClient->submit($this->createLogEvent());
//        $timePassedSeconds = 60;
//        $GLOBALS['currentTime'] += $timePassedSeconds;
//        $this->logHeroClient->submit($this->createLogEvent());
//    }

    private function createLogEvent() {
        $logEvent = new LogEvent();
        return $logEvent
            ->setIpAddress('123.456.78.9')
            ->setHostName('www.example.com')
            ->setLandingPagePath('/home')
            ->setMethod('GET')
            ->setStatusCode('200')
            ->setPageLoadTimeMilliSec(123)
            ->setUserAgent('Firefox')
            ->setReferer('https://www.loghero.io')
            ->setTimestamp(new \DateTime('2018-04-11T06:48:20Z'));
    }

    private function buildExpectedPayload($rows) {
        return json_encode(array(
            'columns' => ['cid','hostname','landingPage','method','statusCode','timestamp', 'pageLoadTime','ip','ua', 'referer'],
            'rows' => $rows
        ));
    }

    private function createLogEventRows($numberOfRows) {
        $rows = array();
        for ($x = 0; $x < $numberOfRows; ++$x) {
            array_push($rows, [
                '4355d3ffc1fd8aa45fc712ed92e23081',
                'www.example.com',
                '/home',
                'GET',
                '200',
                '2018-04-11T06:48:20+00:00',
                123,
                '3ee9e546c0a3811697e424f94ee70bc1',
                'Firefox',
                'https://www.loghero.io'
            ]);
        }
        return $rows;
    }
}
