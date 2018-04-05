<?php
require_once __DIR__ . '/../src/LogHero.php';

use PHPUnit\Framework\TestCase;

class LHClientTest extends TestCase {
    private $apiAccessStub;
    private $logHeroClient;

    public function setUp()
    {
        $this->apiAccessStub = $this->createMock(APIAccess::class);
        $this->logHeroClient = new LHClient($this->apiAccessStub);
    }

    public function testSubmitLogEventToApi() {
        $this->apiAccessStub
            ->expects($this->once())
            ->method('submitLogPackage')
            ->with($this->equalTo($this->buildExpectedPayload([[
                '4355d3ffc1fd8aa45fc712ed92e23081',
                'www.example.com',
                '/home',
                'GET',
                '200',
                '2018-03-31T15:03:01+00:00',
                '3ee9e546c0a3811697e424f94ee70bc1',
                'Firefox'
            ]])));
        $this->logHeroClient->submit($this->createLogEvent());
        $this->logHeroClient->flush();
    }

    private function createLogEvent() {
        $logEvent = new LHLogEvent();
        return $logEvent
            ->setIpAddress('123.456.78.9')
            ->setHostName('www.example.com')
            ->setLandingPagePath('/home')
            ->setMethod('GET')
            ->setStatusCode('200')
            ->setUserAgent('Firefox')
            ->setTimestamp(new DateTime('2018-03-31T15:03:01Z'));
    }

    private function buildExpectedPayload($rows) {
        return json_encode(array(
            'columns' => ['cid','hostname','landingPage','method','statusCode','timestamp','ip','ua'],
            'rows' => $rows
        ));
    }
}
