<?php
namespace LogHero\Client\Test;

use LogHero\Client\APISettingsInterface;
use LogHero\Client\APISettings;
use PHPUnit\Framework\TestCase;
use LogHero\Client\CurlClient;
use LogHero\Client\APIAccess;


class APISettingsTest extends APISettings {

    public function getLogPackageEndpoint() {
        return 'https://test.loghero.io/logs/';
    }
}


class APIAccessForTesting extends APIAccess {
    private $curlClientMock;

    public function __construct($clientId, APISettingsInterface $apiSettings, $curlClientMock) {
        parent::__construct($clientId, $apiSettings);
        $this->curlClientMock = $curlClientMock;
    }

    protected function createCurlClient($url) {
        return $this->curlClientMock;
    }

}

class APIAccessTest extends TestCase {
    private $curlClientMock;
    private $apiKey = 'LH-1234';
    private $clientId = 'Test Client';
    private $apiSettings;
    private $apiAccess;
    private $expectedUserAgent;

    public function setUp() {
        $this->apiSettings = new APISettingsTest($this->apiKey);
        $this->curlClientMock = $this->createMock(CurlClient::class);
        $this->apiAccess = new APIAccessForTesting(
            $this->clientId,
            $this->apiSettings,
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
            $this->clientId,
            new APISettingsTest(null),
            $this->curlClientMock
        );
        $this->curlClientMock
            ->expects($this->never())
            ->method('exec');
        $this->apiAccess->submitLogPackage('LOG DATA');
    }

    /**
     * @expectedException LogHero\Client\APIAccessException
     * @expectedExceptionMessage Call to URL https://test.loghero.io/logs/ failed with status 500; Message: Server error
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
