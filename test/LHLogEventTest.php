<?php
require_once __DIR__ . '/../src/LogHero.php';

use PHPUnit\Framework\TestCase;

class LHLogEventTest extends TestCase {
    private $logEvent;

    public function setUp() {
        $this->logEvent = new LHLogEvent();
    }

    public function testCreateColumns() {
        $this->assertEquals(count($this->logEvent->columns()), 8);
    }

    public function testCreateRowFromLogEventData()
    {
        $this->logEvent
            ->setIpAddress('123.456.78.9')
            ->setHostName('www.example.com')
            ->setLandingPagePath('/home')
            ->setMethod('GET')
            ->setStatusCode('200')
            ->setUserAgent('Firefox')
            ->setTimestamp(new DateTime('2018-03-31T15:03:01Z'));
        $this->assertEquals($this->logEvent->row(), [
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

}
