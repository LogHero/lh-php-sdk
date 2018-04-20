<?php
require_once __DIR__ . '/../src/LogBuffer.php';
require_once __DIR__ . '/../src/LogEvent.php';


use PHPUnit\Framework\TestCase;


class MemLogBufferTest extends TestCase {
    private $logBuffer;

    public function setUp() {
        parent::setUp();
        $this->logBuffer = new MemLogBuffer(100);
    }

    public function testAppendLogEvents() {
        $this->logBuffer->push($this->createLogEvent('/page-1'));
        $this->logBuffer->push($this->createLogEvent('/page-2'));
        $this->logBuffer->push($this->createLogEvent('/page-3'));
        $this->assertEquals($this->logBuffer->sizeInBytes(), 300);
    }
    
    public function testDumpLogEvents() {
        $this->logBuffer->push($this->createLogEvent('/page-1'));
        $this->logBuffer->push($this->createLogEvent('/page-2'));
        $this->logBuffer->push($this->createLogEvent('/page-3'));
        $logEvents = $this->logBuffer->dump();
        $this->assertEquals($this->logBuffer->sizeInBytes(), 0);
        $this->assertLandingPagePathsInLogEvents($logEvents, array(
            '/page-1',
            '/page-2',
            '/page-3'
        ));
        $this->logBuffer->push($this->createLogEvent('/page-4'));
        $this->logBuffer->push($this->createLogEvent('/page-5'));
        $logEvents = $this->logBuffer->dump();
        $this->assertEquals($this->logBuffer->sizeInBytes(), 0);
        $this->assertLandingPagePathsInLogEvents($logEvents, array(
            '/page-4',
            '/page-5'
        ));
    }

    private function createLogEvent($landingPagePath) {
        $logEvent = new LHLogEvent();
        $logEvent
            ->setUserAgent('Firefox')
            ->setIpAddress('123.45.67.89')
            ->setHostname('local.loghero.io')
            ->setLandingPagePath($landingPagePath)
            ->setMethod('GET')
            ->setStatusCode(200)
            ->setTimestamp(new DateTime('2018-03-31T15:03:01Z'));
        return $logEvent;
    }

    private function assertLandingPagePathsInLogEvents($logEvents, $landingPagePaths) {
        $this->assertEquals(count($logEvents), count($landingPagePaths));
        for ($i = 0; $i < count($logEvents); ++$i) {
            $this->assertEquals($logEvents[$i]->row()[2], $landingPagePaths[$i]);
        }
    }
    
}
