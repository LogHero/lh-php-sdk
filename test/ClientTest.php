<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/LogHero.php';
require_once __DIR__ . '/../src/LogBuffer.php';


use PHPUnit\Framework\TestCase;


$currentTime = null;
function microtime() {
    return $GLOBALS['currentTime'];
}


class ClientTest extends TestCase {
    private $apiAccessStub;
    private $logHeroClient;
    private $maxRecordSizeInBytes = 500;
    private $maxTimeIntervalSeconds = 150;

    public function setUp()
    {
        $GLOBALS['currentTime'] = 1523429300.8000;
        $this->apiAccessStub = $this->createMock(APIAccess::class);
        $this->logHeroClient = new Client(
            $this->apiAccessStub,
            new MemLogBuffer(100),
            $this->maxRecordSizeInBytes,
            $this->maxTimeIntervalSeconds
        );
    }

    public function testSubmitNothingIfNoLogsRecorded() {
        $this->apiAccessStub
            ->expects($this->never())
            ->method('submitLogPackage');
        $this->logHeroClient->flush();
    }

    public function testSubmitLogEventToApi() {
        $this->apiAccessStub
            ->expects($this->once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload($this->createLogEventRows(1))));
        $this->logHeroClient->submit($this->createLogEvent());
        $this->logHeroClient->flush();
    }

    public function testSubmitLogEventsIfRecordSizeIsReached() {
        $this->apiAccessStub
            ->expects($this->exactly(2))
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload($this->createLogEventRows(5))));
        for ($x = 0; $x < 11; ++$x) {
            $this->logHeroClient->submit($this->createLogEvent());
        }
    }

    public function testSubmitLogEventsIfMaximumTimeIntervalIsReached() {
        $this->apiAccessStub
            ->expects($this->once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload($this->createLogEventRows(3))));
        $this->logHeroClient->submit($this->createLogEvent());
        $timePassedSeconds = 120;
        $GLOBALS['currentTime'] += $timePassedSeconds;
        $this->logHeroClient->submit($this->createLogEvent());
        $timePassedSeconds = 60;
        $GLOBALS['currentTime'] += $timePassedSeconds;
        $this->logHeroClient->submit($this->createLogEvent());
    }

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
            ->setTimestamp(new \DateTime('2018-04-11T06:48:20Z'));
    }

    private function buildExpectedPayload($rows) {
        return json_encode(array(
            'columns' => ['cid','hostname','landingPage','method','statusCode','timestamp', 'pageLoadTime','ip','ua'],
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
                'Firefox'
            ]);
        }
        return $rows;
    }
}
