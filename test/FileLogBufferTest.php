<?php
require_once __DIR__ . '/../src/LogBuffer.php';
require_once __DIR__ . '/../src/LogEvent.php';
require_once __DIR__ . '/Util.php';


use PHPUnit\Framework\TestCase;


class FileLogBufferTest extends TestCase {
    private $bufferFileLocation = __DIR__ . '/buffer.loghero.io.txt';
    private $logBuffer;

    public function setUp() {
        parent::setUp();
        $this->logBuffer = new FileLogBuffer($this->bufferFileLocation);
    }

    public function tearDown() {
        parent::tearDown();
        if(file_exists($this->bufferFileLocation)) {
            unlink($this->bufferFileLocation);
        }
    }

    public function testCreateBufferFileWhenFirstEventArrives() {
        $this->assertFileNotExists($this->bufferFileLocation);
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->assertFileExists($this->bufferFileLocation);
    }

    public function testGetSizeInBytes() {
        $this->assertEquals(0, $this->logBuffer->sizeInBytes());
        $this->logBuffer->push(createLogEvent('/page-1'));
        clearstatcache();
        $this->assertEquals(226, $this->logBuffer->sizeInBytes());
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->logBuffer->push(createLogEvent('/page-3'));
        clearstatcache();
        $this->assertEquals(678, $this->logBuffer->sizeInBytes());
    }
    
}
