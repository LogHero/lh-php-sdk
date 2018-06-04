<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/LogBuffer.php';
require_once __DIR__ . '/../src/transport/AsyncLogTransport.php';
require_once __DIR__ . '/Util.php';
require_once __DIR__ . '/MicrotimeMock.php';

use PHPUnit\Framework\TestCase;


class AsyncLogTransportForTesting extends AsyncLogTransport {
    private $curlClientMock;

    public function __construct(
        LogBuffer $logBuffer,
        APIAccess $apiAccess,
        $clientId,
        $apiKey,
        $triggerEndpoint,
        $curlClientMock
    ) {
        parent::__construct($logBuffer, $apiAccess, $clientId, $apiKey, $triggerEndpoint);
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
        $apiKey = 'LH-1234';
        $this->curlClientMock = $this->createMock(CurlClient::class);
        $this->flushStrategy = new AsyncLogTransportForTesting(
            $this->logBuffer,
            $this->apiAccessStub,
            $this->clientId,
            $apiKey,
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
                'Authorization: LH-1234',
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
}