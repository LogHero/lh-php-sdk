<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\CurlClient;
use LogHero\Client\APIAccess;
use LogHero\Client\MemLogBuffer;
use LogHero\Client\APIAccessInterface;
use LogHero\Client\LogBufferInterface;
use LogHero\Client\AsyncLogTransport;
use LogHero\Client\AsyncFlushFailedException;


class AsyncLogTransportForTesting extends AsyncLogTransport {
    private $curlClientMock;

    public function __construct(
        LogBufferInterface $logBuffer,
        APIAccessInterface $apiAccess,
        $clientId,
        $secret,
        $triggerEndpoint,
        $curlClientMock
    ) {
        parent::__construct($logBuffer, $apiAccess, $clientId, $secret, $triggerEndpoint);
        $this->curlClientMock = $curlClientMock;
    }

    protected function createCurlClient($url) {
        return $this->curlClientMock;
    }
}


class AsyncLogTransportTest extends TestCase {
    private $logBuffer;
    private $flushStrategy;
    private $apiAccessStub;
    private $clientId;
    private $curlClientMock;

    public function setUp() {
        $GLOBALS['currentTime'] = 1523429300.8000;
        $this->logBuffer = new MemLogBuffer();
        $this->apiAccessStub = $this->createMock(APIAccess::class);
        $this->clientId = 'test-client';
        $triggerEndpoint = '/flush.php';
        $authorizationToken = 'LH-1234';
        $this->curlClientMock = $this->createMock(CurlClient::class);
        $this->flushStrategy = new AsyncLogTransportForTesting(
            $this->logBuffer,
            $this->apiAccessStub,
            $this->clientId,
            $authorizationToken,
            $triggerEndpoint,
            $this->curlClientMock
        );
    }

    public function testHitEndpointToTriggerAsyncFlush() {
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->apiAccessStub
            ->expects(static::never())
            ->method('submitLogPackage');
        $this->curlClientMock
            ->expects($this->at(0))
            ->method('setOpt')
            ->with($this->equalTo(CURLOPT_HTTPHEADER), $this->equalTo(array(
                'Token: LH-1234',
                'User-Agent: '.$this->clientId
            )));
        $this->curlClientMock
            ->expects($this->at(1))
            ->method('setOpt')
            ->with($this->equalTo(CURLOPT_CUSTOMREQUEST), $this->equalTo('GET'));
        $this->curlClientMock
            ->expects($this->once())
            ->method('exec');
        $this->curlClientMock
            ->expects($this->once())
            ->method('getInfo')
            ->willReturn(200);
        $this->curlClientMock
            ->expects($this->once())
            ->method('close');
        $this->flushStrategy->flush();
    }

    public function testDumpLogEventsToApi() {
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
        $this->flushStrategy->dumpLogEvents();
    }
    /**
     * @expectedException LogHero\Client\AsyncFlushFailedException
     * @expectedExceptionMessage LogHero\Client\APIAccessException: Call to URL /flush.php failed with status 403; Message:
     */
    public function testDumpSyncIfAsyncTriggerFailed() {
        $this->logBuffer->push(createLogEvent('/page-1'));
        $this->logBuffer->push(createLogEvent('/page-2'));
        $this->curlClientMock
            ->expects($this->once())
            ->method('getInfo')
            ->willReturn(403);
        $this->apiAccessStub
            ->expects($this->once())
            ->method('submitLogPackage')
            ->with($this->equalTo(buildExpectedPayloadForLogEvents(array(
                createLogEvent('/page-1'),
                createLogEvent('/page-2')
            ))));
        $this->flushStrategy->flush();
    }
}