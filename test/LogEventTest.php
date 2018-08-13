<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\LogEvent;


class LogEventTest extends TestCase {
    private $refererColumnIdx = 11;

    public function testCreateColumns() {
        $logEvent = $this->createValidLogEvent();
        $this->assertEquals(count($logEvent->columns()), 12);
    }

    public function testCreateRowFromLogEventData() {
        $logEvent = $this->createValidLogEvent();
        $this->assertEquals($logEvent->row(), [
            '4355d3ffc1fd8aa45fc712ed92e23081',
            'www.example.com',
            'https',
            '/home',
            'GET',
            '200',
            '2018-03-31T15:03:01+00:00',
            150,
            '3ee9e546c0a3811697e424f94ee70bc1',
            '202cb962ac59075b964b07152d234b70.250cf8b51c773f3f8dc8b4be867a9a02.35f4a8d465e6e1edc05f3d8ab658c551.45c48cce2e2d7fbdea1afc51c7c6ad26',
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
            'https',
            '/home',
            'GET',
            '200',
            '2018-03-31T15:03:01+00:00',
            null,
            '3ee9e546c0a3811697e424f94ee70bc1',
            '202cb962ac59075b964b07152d234b70.250cf8b51c773f3f8dc8b4be867a9a02.35f4a8d465e6e1edc05f3d8ab658c551.45c48cce2e2d7fbdea1afc51c7c6ad26',
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
            ->setProtocol('https')
            ->setLandingPagePath('/home')
            ->setMethod('GET')
            ->setStatusCode('200')
            ->setUserAgent('Firefox')
            ->setPageLoadTimeMilliSec(150)
            ->setTimestamp(new \DateTime('2018-03-31T15:03:01Z'));
        return $logEvent;
    }

}
