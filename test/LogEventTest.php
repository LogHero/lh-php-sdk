<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/LogHero.php';

use PHPUnit\Framework\TestCase;

class LogEventTest extends TestCase {
    private $refererColumnIdx = 9;

    public function testCreateColumns() {
        $logEvent = $this->createValidLogEvent();
        $this->assertEquals(count($logEvent->columns()), 10);
    }

    public function testCreateRowFromLogEventData() {
        $logEvent = $this->createValidLogEvent();
        $this->assertEquals($logEvent->row(), [
            '4355d3ffc1fd8aa45fc712ed92e23081',
            'www.example.com',
            '/home',
            'GET',
            '200',
            '2018-03-31T15:03:01+00:00',
            150,
            '3ee9e546c0a3811697e424f94ee70bc1',
            'Firefox',
            null
        ]);
    }

    /**
     * @expectedException LogHero\Client\InvalidLogEventException
     * @expectedExceptionMessage Log event is incomplete: Landing page path is null
     */
    public function testVerifyLandingPagePathSet() {
        $logEvent = $this->createValidLogEvent();
        $logEvent->setLandingPagePath(null);
        $logEvent->row();
    }

    /**
     * @expectedException LogHero\Client\InvalidLogEventException
     */
    public function testVerifyUserAgentSet() {
        $logEvent = $this->createValidLogEvent();
        $logEvent->setUserAgent(null);
        $logEvent->row();
    }

    /**
     * @expectedException LogHero\Client\InvalidLogEventException
     */
    public function testVerifyIpAddressSet() {
        $logEvent = $this->createValidLogEvent();
        $logEvent->setIpAddress(null);
        $logEvent->row();
    }

    /**
     * @expectedException LogHero\Client\InvalidLogEventException
     */
    public function testVerifyHostnameSet() {
        $logEvent = $this->createValidLogEvent();
        $logEvent->setHostname(null);
        $logEvent->row();
    }

    /**
     * @expectedException LogHero\Client\InvalidLogEventException
     */
    public function testVerifyTimestampSet() {
        $logEvent = new LogEvent();
        $logEvent
            ->setIpAddress('123.456.78.9')
            ->setHostName('www.example.com')
            ->setLandingPagePath('/home')
            ->setMethod('GET')
            ->setStatusCode('200')
            ->setUserAgent('Firefox');
        $logEvent->row();
    }

    public function testLoadTimeIsOptional() {
        $logEvent = $this->createValidLogEvent();
        $logEvent->setPageLoadTimeMilliSec(null);
        $this->assertEquals($logEvent->row(), [
            '4355d3ffc1fd8aa45fc712ed92e23081',
            'www.example.com',
            '/home',
            'GET',
            '200',
            '2018-03-31T15:03:01+00:00',
            null,
            '3ee9e546c0a3811697e424f94ee70bc1',
            'Firefox',
            null
        ]);
        $logEvent->row();
    }

    public function testSendReferer() {
        $logEvent = $this->createValidLogEvent();
        $this->assertNull($logEvent->row()[$this->refererColumnIdx]);
        $logEvent->setReferer('https://www.loghero.io');
        $this->assertEquals($logEvent->row()[$this->refererColumnIdx], 'https://www.loghero.io');
    }

    public function testDoNotSendRefererIfDirect() {
        $logEvent = $this->createValidLogEvent();
        $logEvent->setReferer('direct');
        $this->assertNull($logEvent->row()[$this->refererColumnIdx]);
    }

    private function createValidLogEvent() {
        $logEvent = new LogEvent();
        $logEvent
            ->setIpAddress('123.456.78.9')
            ->setHostName('www.example.com')
            ->setLandingPagePath('/home')
            ->setMethod('GET')
            ->setStatusCode('200')
            ->setUserAgent('Firefox')
            ->setPageLoadTimeMilliSec(150)
            ->setTimestamp(new \DateTime('2018-03-31T15:03:01Z'));
        return $logEvent;
    }

}
