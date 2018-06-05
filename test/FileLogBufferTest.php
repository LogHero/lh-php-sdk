<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/buffer/FileLogBuffer.php';
require_once __DIR__ . '/../src/event/LogEvent.php';
require_once __DIR__ . '/Util.php';
require_once __DIR__ . '/MicrotimeMock.php';


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
    private $maxDumpTimeIntervalSeconds = 30;
    private $bufferFileLocation = __DIR__ . '/buffer.loghero.io.txt';
    private $lastDumpTimestampFileLocation = __DIR__ . '/buffer.loghero.io.last-dump.timestamp';
    private $dumpedLogEventsResultFile = __DIR__ . '/buffer-dumped.loghero.io.txt';
    private $logBuffer;
    private $microtimeMock;

    public function setUp() {
        parent::setUp();
        $GLOBALS['currentTime'] = \microtime(true);
        $this->microtimeMock = createMicrotimeMock('LogHero\\Client');
        $this->microtimeMock->enable();
        $maxBufferFileSizeInBytes = 1000;
        $this->logBuffer = new FileLogBuffer($this->bufferFileLocation, $maxBufferFileSizeInBytes);
    }

    public function tearDown() {
        parent::tearDown();
        if(file_exists($this->bufferFileLocation)) {
            unlink($this->bufferFileLocation);
        }
        if(file_exists($this->lastDumpTimestampFileLocation)) {
            unlink($this->lastDumpTimestampFileLocation);
        }
        if(file_exists($this->dumpedLogEventsResultFile)) {
            unlink($this->dumpedLogEventsResultFile);
        }
        $this->microtimeMock->disable();
    }

    public function testCreateBufferFileWhenFirstEventArrives() {
        static::assertFileNotExists($this->bufferFileLocation);
        $this->logBuffer->push(createLogEvent('/page-1'));
        static::assertFileExists($this->bufferFileLocation);
    }

    public function testNeedsDumping() {
        static::assertFalse($this->logBuffer->needsDumping());
        $this->logBuffer->push(createLogEvent('/page-1'));
        clearstatcache();
        static::assertTrue($this->logBuffer->needsDumping());
        $this->logBuffer->dump();
        clearstatcache();
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->logBuffer->push(createLogEvent('/page-3'));
        static::assertFalse($this->logBuffer->needsDumping());
        clearstatcache();
        $this->logBuffer->push(createLogEvent('/page-4'));
        static::assertTrue($this->logBuffer->needsDumping());
    }

    public function testDeleteBufferFileOnDump() {
        $logEvents = $this->logBuffer->dump();
        static::assertEmpty($logEvents);
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
    
    public function testAcquireDumpIfMaxTimeIntervalIsReached() {
        static::assertFalse($this->logBuffer->needsDumping());
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->logBuffer->push(createLogEvent('/page-2'));
        static::assertFileNotExists($this->lastDumpTimestampFileLocation);
        static::assertTrue($this->logBuffer->needsDumping());
        $this->logBuffer->dump();
        static::assertFileExists($this->lastDumpTimestampFileLocation);
        $this->logBuffer->push(createLogEvent('/page-3'));
        $GLOBALS['currentTime'] = \microtime(true);
        static::assertFalse($this->logBuffer->needsDumping());
        $GLOBALS['currentTime'] = \microtime(true) + $this->maxDumpTimeIntervalSeconds * 1000;
        static::assertTrue($this->logBuffer->needsDumping());
    }

    public function testHandlesConcurrentAccess() {
        static::assertFileNotExists($this->dumpedLogEventsResultFile);
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
        static::assertEquals($numberOfThreads * $logEventsPerThread, count($logEvents));
    }

}
