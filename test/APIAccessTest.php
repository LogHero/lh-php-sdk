<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/APIAccess.php';

use PHPUnit\Framework\TestCase;

class APIAccessCurlForTesting extends APIAccessCurl {
    private $curlClientMock;

    public function __construct($apiKey, $clientId, $apiLogPackageEndpoint, $curlClientMock) {
        parent::__construct($apiKey, $clientId, $apiLogPackageEndpoint);
        $this->curlClientMock = $curlClientMock;
    }

    protected function createCurlClient($url) {
        return $this->curlClientMock;
    }

}

class APIAccessCurlTest extends TestCase {
    private $curlClientMock;
    private $apiKey = 'LH-1234';
    private $clientId = 'Test Client';
    private $endoint = 'https://www.loghero.io/logs/';
    private $apiAccess;
    private $expectedUserAgent;

    public function setUp() {
        $this->curlClientMock = $this->createMock(CurlClient::class);
        $this->apiAccess = new APIAccessCurlForTesting(
            $this->apiKey,
            $this->clientId,
            $this->endoint,
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

    /**
     * @expectedException LogHero\Client\APIAccessException
     * @expectedExceptionMessage Call to URL https://www.loghero.io/logs/ failed with status 500; Message: Server error
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
