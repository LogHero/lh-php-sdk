<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\FileLogBuffer;


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
    private $bufferFileLocationNoPermissions = __DIR__ . '/buffer.loghero.io.no-permissions.txt';
    private $logBuffer;
    private $microtimeMock;

    public function setUp() {
        parent::setUp();
        $GLOBALS['currentTime'] = \microtime(true);
        $this->microtimeMock = createMicrotimeMock();
        $this->microtimeMock->enable();
        $flushBufferFileSizeInBytes = 1000;
        $this->logBuffer = new FileLogBuffer($this->bufferFileLocation, $flushBufferFileSizeInBytes);
        file_put_contents($this->bufferFileLocationNoPermissions, 'DATA');
        chmod($this->bufferFileLocationNoPermissions, 0400);
        clearstatcache();
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
        chmod($this->bufferFileLocationNoPermissions, 0700);
        unlink($this->bufferFileLocationNoPermissions);
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
        // False even though we reach the flush buffer size
        // This is because we read the buffer file size before pushing the log event to check if the max buffer size is already reached
        // The size of the current log event is considered when adding the next log event to the buffer
        static::assertFalse($this->logBuffer->needsDumping());
        clearstatcache();
        $this->logBuffer->push(createLogEvent('/page-5'));
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

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Inconsistent configuration: $maxPushBufferFileSizeInBytes is smaller than $flushBufferFileSizeInBytes: 100 <= 1000
     */
    public function testVerifyLogBufferConfiguration() {
        new FileLogBuffer($this->bufferFileLocation, 1000, 300, 100);
    }

    /**
     * @expectedException \LogHero\Client\BufferSizeExceededException
     * @expectedExceptionMessage Maximum buffer size reached (1000 Bytes)! Pushing further log events is prohibited to avoid running out of disk space
     */
    public function testRaiseBufferSizeExceededIfMaxBufferSizeIsReached() {
        $logBuffer = new FileLogBuffer($this->bufferFileLocation, 100, 300, 1000);
        clearstatcache();
        $logBuffer->push(createLogEvent('/page-1'));
        clearstatcache();
        $logBuffer->push(createLogEvent('/page-2'));
        clearstatcache();
        $logBuffer->push(createLogEvent('/page-3'));
        clearstatcache();
        $logBuffer->push(createLogEvent('/page-4'));
    }

    /**
     * @expectedException \LogHero\Client\PermissionDeniedException
     * @expectedExceptionMessage Permission denied! Cannot write to file
     */
    public function testRaisePermissionDeniedExceptionIfNoWritePermissionsOnBufferFile() {
        $logBuffer = new FileLogBuffer($this->bufferFileLocationNoPermissions, 100, 300, 1000);
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
