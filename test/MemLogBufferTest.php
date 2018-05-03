<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/LogBuffer.php';
require_once __DIR__ . '/../src/LogEvent.php';
require_once __DIR__ . '/Util.php';


use PHPUnit\Framework\TestCase;


class MemLogBufferTest extends TestCase {
    private $logBuffer;

    public function setUp() {
        parent::setUp();
        $this->logBuffer = new MemLogBuffer(100);
    }

    public function testAppendLogEvents() {
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->logBuffer->push(createLogEvent('/page-3'));
        $this->assertEquals($this->logBuffer->sizeInBytes(), 300);
    }
    
    public function testDumpLogEvents() {
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->logBuffer->push(createLogEvent('/page-3'));
        $logEvents = $this->logBuffer->dump();
        $this->assertEquals($this->logBuffer->sizeInBytes(), 0);
        assertLandingPagePathsInLogEvents($this, $logEvents, array(
            '/page-1',
            '/page-2',
            '/page-3'
        ));
        $this->logBuffer->push(createLogEvent('/page-4'));
        $this->logBuffer->push(createLogEvent('/page-5'));
        $logEvents = $this->logBuffer->dump();
        $this->assertEquals($this->logBuffer->sizeInBytes(), 0);
        assertLandingPagePathsInLogEvents($this, $logEvents, array(
            '/page-4',
            '/page-5'
        ));
    }

    public function testGetFirstLogEvent() {
        $this->assertNull($this->logBuffer->getFirstLogEvent());
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->assertEquals($this->logBuffer->getFirstLogEvent()->row()[2], '/page-1');
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->logBuffer->push(createLogEvent('/page-3'));
        $this->assertEquals($this->logBuffer->getFirstLogEvent()->row()[2], '/page-1');
    }

}
