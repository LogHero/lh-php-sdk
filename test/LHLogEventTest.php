<?php
require_once __DIR__ . '/../src/LogHero.php';

use PHPUnit\Framework\TestCase;

class LHLogEventTest extends TestCase {

    public function testCreateColumns() {
        $logEvent = $this->createValidLogEvent();
        $this->assertEquals(count($logEvent->columns()), 8);
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
            '3ee9e546c0a3811697e424f94ee70bc1',
            'Firefox'
        ]);
    }

    /**
     * @expectedException InvalidLogEventException
     * @expectedExceptionMessage Log event is incomplete: Landing page path is null
     */
    public function testVerifyLandingPagePathSet() {
        $logEvent = $this->createValidLogEvent();
        $logEvent->setLandingPagePath(null);
        $logEvent->row();
    }

    /**
     * @expectedException InvalidLogEventException
     */
    public function testVerifyUserAgentSet() {
        $logEvent = $this->createValidLogEvent();
        $logEvent->setUserAgent(null);
        $logEvent->row();
    }

    /**
     * @expectedException InvalidLogEventException
     */
    public function testVerifyIpAddressSet() {
        $logEvent = $this->createValidLogEvent();
        $logEvent->setIpAddress(null);
        $logEvent->row();
    }

    /**
     * @expectedException InvalidLogEventException
     */
    public function testVerifyHostnameSet() {
        $logEvent = $this->createValidLogEvent();
        $logEvent->setHostname(null);
        $logEvent->row();
    }

    /**
     * @expectedException InvalidLogEventException
     */
    public function testVerifyTimestampSet() {
        $logEvent = new LHLogEvent();
        $logEvent
            ->setIpAddress('123.456.78.9')
            ->setHostName('www.example.com')
            ->setLandingPagePath('/home')
            ->setMethod('GET')
            ->setStatusCode('200')
            ->setUserAgent('Firefox');
        $logEvent->row();
    }

    private function createValidLogEvent() {
        $logEvent = new LHLogEvent();
        $logEvent
            ->setIpAddress('123.456.78.9')
            ->setHostName('www.example.com')
            ->setLandingPagePath('/home')
            ->setMethod('GET')
            ->setStatusCode('200')
            ->setUserAgent('Firefox')
            ->setTimestamp(new DateTime('2018-03-31T15:03:01Z'));
        return $logEvent;
    }

}
