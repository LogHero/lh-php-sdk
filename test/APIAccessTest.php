<?php
namespace LogHero\Client\Test;

use LogHero\Client\APIKeyMemStorage;
use LogHero\Client\APIKeyStorageInterface;
use PHPUnit\Framework\TestCase;
use LogHero\Client\CurlClient;
use LogHero\Client\APIAccess;


class APIAccessForTesting extends APIAccess {
    private $curlClientMock;

    public function __construct(APIKeyStorageInterface $apiKeyStorage, $clientId, $apiLogPackageEndpoint, $curlClientMock) {
        parent::__construct($apiKeyStorage, $clientId, $apiLogPackageEndpoint);
        $this->curlClientMock = $curlClientMock;
    }

    protected function createCurlClient($url) {
        return $this->curlClientMock;
    }

}

class APIAccessTest extends TestCase {
    private $curlClientMock;
    private $apiKey = 'LH-1234';
    private $apiKeyStorage;
    private $clientId = 'Test Client';
    private $endpoint = 'https://api.loghero.io/logs/';
    private $apiAccess;
    private $expectedUserAgent;

    public function setUp() {
        $this->apiKeyStorage = new APIKeyMemStorage();
        $this->apiKeyStorage->setKey($this->apiKey);
        $this->curlClientMock = $this->createMock(CurlClient::class);
        $this->apiAccess = new APIAccessForTesting(
            $this->apiKeyStorage,
            $this->clientId,
            $this->endpoint,
            $this->curlClientMock
        );
        $composerPackage = file_get_contents(__DIR__.'/../composer.json');
        $composerPackage = json_decode($composerPackage,true);
        $this->expectedUserAgent = 'Test Client; PHP SDK '.$composerPackage['name'].'@'.$composerPackage['version'];
    }

    public function testPostPayload() {
        $this->curlClientMock
            ->expects($this->at(0))
            ->method('setOpt')
            ->with($this->equalTo(CURLOPT_HTTPHEADER), $this->equalTo(array(
                'Content-type: application/json',
                'Content-encoding: deflate',
                'Authorization: LH-1234',
                'User-Agent: '.$this->expectedUserAgent
            )));
        $this->curlClientMock
            ->expects($this->at(1))
            ->method('setOpt')
            ->with($this->equalTo(CURLOPT_CUSTOMREQUEST), $this->equalTo('PUT'));
        $this->curlClientMock
            ->expects($this->at(2))
            ->method('setOpt')
            ->with($this->equalTo(CURLOPT_POSTFIELDS), $this->equalTo(gzcompress('LOG DATA')));
        $this->curlClientMock
            ->expects($this->once())
            ->method('exec');
        $this->curlClientMock
            ->expects($this->once())
            ->method('getInfo')
            ->willReturn(201);
        $this->curlClientMock
            ->expects($this->once())
            ->method('close');
        $this->apiAccess->submitLogPackage('LOG DATA');
    }

    public function testNoPostIfApiKeyInvalid() {
        $this->apiAccess = new APIAccessForTesting(
            new APIKeyMemStorage(),
            $this->clientId,
            $this->endpoint,
            $this->curlClientMock
        );
        $this->curlClientMock
            ->expects($this->never())
            ->method('exec');
        $this->apiAccess->submitLogPackage('LOG DATA');
    }

    /**
     * @expectedException LogHero\Client\APIAccessException
     * @expectedExceptionMessage Call to URL https://api.loghero.io/logs/ failed with status 500; Message: Server error
     */
    public function testExceptionIfErrorResponse() {
        $this->curlClientMock
            ->expects($this->once())
            ->method('getInfo')
            ->willReturn(500);
        $this->curlClientMock
            ->expects($this->once())
            ->method('error')
            ->willReturn('Server error');
        $this->curlClientMock
            ->expects($this->once())
            ->method('close');
        $this->apiAccess->submitLogPackage('LOG DATA');
    }

}
