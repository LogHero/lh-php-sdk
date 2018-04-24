<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/LogBuffer.php';
require_once __DIR__ . '/../src/LogEvent.php';
require_once __DIR__ . '/Util.php';


use PHPUnit\Framework\TestCase;


function getmypid() {
    return 55;
}


class FileLogBufferTest extends TestCase {
    private $bufferFileBaseName = __DIR__ . '/buffer.loghero.io';
    private $expectedBufferFileLocation = __DIR__ . '/buffer.loghero.io.55.txt';
    private $logBuffer;

    public function setUp() {
        parent::setUp();
        $this->logBuffer = new FileLogBuffer($this->bufferFileBaseName);
    }

    public function tearDown() {
        parent::tearDown();
        if(file_exists($this->expectedBufferFileLocation)) {
            unlink($this->expectedBufferFileLocation);
        }
    }

    public function testCreateBufferFileWhenFirstEventArrives() {
        $this->assertFileNotExists($this->expectedBufferFileLocation);
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->assertFileExists($this->expectedBufferFileLocation);
    }

    public function testGetSizeInBytes() {
        $this->assertEquals(0, $this->logBuffer->sizeInBytes());
        $this->logBuffer->push(createLogEvent('/page-1'));
        clearstatcache();
        $this->assertEquals(343, $this->logBuffer->sizeInBytes());
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->logBuffer->push(createLogEvent('/page-3'));
        clearstatcache();
        $this->assertEquals(1029, $this->logBuffer->sizeInBytes());
    }

    public function testDeleteBufferFileOnDump() {
        $logEvents = $this->logBuffer->dump();
        $this->assertEmpty($logEvents);
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->logBuffer->push(createLogEvent('/page-3'));
        clearstatcache();
        $this->assertEquals(1029, $this->logBuffer->sizeInBytes());
        $logEvents = $this->logBuffer->dump();
        assertLandingPagePathsInLogEvents($this, $logEvents, array(
            '/page-1',
            '/page-2',
            '/page-3'
        ));
        clearstatcache();
        $this->assertEquals(0, $this->logBuffer->sizeInBytes());
        $this->logBuffer->push(createLogEvent('/page-4'));
        $this->logBuffer->push(createLogEvent('/page-5'));
        $logEvents = $this->logBuffer->dump();
        assertLandingPagePathsInLogEvents($this, $logEvents, array(
            '/page-4',
            '/page-5'
        ));
    }
    
}
