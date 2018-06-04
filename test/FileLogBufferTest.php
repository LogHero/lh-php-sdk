<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/buffer/FileLogBuffer.php';
require_once __DIR__ . '/../src/event/LogEvent.php';
require_once __DIR__ . '/Util.php';


use PHPUnit\Framework\TestCase;


class LogEventWorkerThread extends \GPhpThread {
    private $numberOfLogEventsToWrite;
    private $logBuffer;
    private $resultBuffer;

    public function __construct(
        $numberOfLogEventsToWrite,
        $bufferFileLocation,
        $dumpedLogEventsResultFile,
        $sharedCriticalSection
    )
    {
        parent::__construct($sharedCriticalSection, false);
        $this->numberOfLogEventsToWrite = $numberOfLogEventsToWrite;
        $this->logBuffer = new FileLogBuffer($bufferFileLocation, 1000);
        $this->resultBuffer = new FileLogBuffer($dumpedLogEventsResultFile, 1000);
    }

    public function run() {
        for($i=0; $i<$this->numberOfLogEventsToWrite; ++$i) {
            $this->logBuffer->push(createLogEvent('/page'));
        }
        $logEventsDumped = $this->logBuffer->dump();
        foreach($logEventsDumped as $logEvent) {
            $this->resultBuffer->push($logEvent);
        }
    }

}


class FileLogBufferTest extends TestCase {
    private $bufferFileLocation = __DIR__ . '/buffer.loghero.io.txt';
    private $dumpedLogEventsResultFile = __DIR__ . '/buffer-dumped.loghero.io.txt';
    private $logBuffer;

    public function setUp() {
        parent::setUp();
        $maxBufferFileSizeInBytes = 1000;
        $this->logBuffer = new FileLogBuffer($this->bufferFileLocation, $maxBufferFileSizeInBytes);
    }

    public function tearDown() {
        parent::tearDown();
        if(file_exists($this->bufferFileLocation)) {
            unlink($this->bufferFileLocation);
        }
        if(file_exists($this->dumpedLogEventsResultFile)) {
            unlink($this->dumpedLogEventsResultFile);
        }
    }

    public function testCreateBufferFileWhenFirstEventArrives() {
        $this->assertFileNotExists($this->bufferFileLocation);
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->assertFileExists($this->bufferFileLocation);
    }

    public function testNeedsDumping() {
        static::assertFalse($this->logBuffer->needsDumping());
        $this->logBuffer->push(createLogEvent('/page-1'));
        clearstatcache();
        static::assertFalse($this->logBuffer->needsDumping());
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->logBuffer->push(createLogEvent('/page-3'));
        clearstatcache();
        static::assertTrue($this->logBuffer->needsDumping());
    }

    public function testDeleteBufferFileOnDump() {
        $logEvents = $this->logBuffer->dump();
        $this->assertEmpty($logEvents);
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->logBuffer->push(createLogEvent('/page-3'));
        clearstatcache();
        $logEvents = $this->logBuffer->dump();
        assertLandingPagePathsInLogEvents($this, $logEvents, array(
            '/page-1',
            '/page-2',
            '/page-3'
        ));
        clearstatcache();
        $this->logBuffer->push(createLogEvent('/page-4'));
        $this->logBuffer->push(createLogEvent('/page-5'));
        $logEvents = $this->logBuffer->dump();
        assertLandingPagePathsInLogEvents($this, $logEvents, array(
            '/page-4',
            '/page-5'
        ));
    }

//    public function testGetFirstLogEvent() {
//        $this->assertNull($this->logBuffer->getFirstLogEvent());
//        $this->logBuffer->push(createLogEvent('/page-1'));
//        $this->assertEquals($this->logBuffer->getFirstLogEvent()->row()[2], '/page-1');
//        $this->logBuffer->push(createLogEvent('/page-2'));
//        $this->logBuffer->push(createLogEvent('/page-3'));
//        $this->assertEquals($this->logBuffer->getFirstLogEvent()->row()[2], '/page-1');
//    }

    public function testHandlesConcurrentAccess() {
        $this->assertFileNotExists($this->dumpedLogEventsResultFile);
        $criticalSection = null;
        $numberOfThreads = 30;
        $logEventsPerThread = 40;
        $threads = array();
        for ($i=0; $i<$numberOfThreads; ++$i) {
            $newThread = new LogEventWorkerThread(
                $logEventsPerThread,
                $this->bufferFileLocation,
                $this->dumpedLogEventsResultFile,
                $criticalSection
            );
            $newThread->start();
            array_push($threads, $newThread);
        }
        foreach($threads as $workerThread) {
            $workerThread->join();
        }
        $resultBuffer = new FileLogBuffer($this->dumpedLogEventsResultFile, 1000);
        $logEvents = $resultBuffer->dump();
        $this->assertEquals($numberOfThreads * $logEventsPerThread, count($logEvents));
    }

}
