<?php
namespace LogHero\Client;

use PHPUnit\Framework\TestCase;


class DebugLogEventTest extends TestCase {

    public function testSendsRawIpAddress() {
        $logEvent = new DebugLogEvent();
        $logEvent
            ->setIpAddress('123.456.78.9')
            ->setHostName('www.example.com')
            ->setLandingPagePath('/home')
            ->setMethod('GET')
            ->setStatusCode('200')
            ->setUserAgent('Firefox')
            ->setTimestamp(new \DateTime('2018-03-31T15:03:01Z'));
        $this->assertEquals($logEvent->columns()[10], 'rawIp');
        $this->assertEquals($logEvent->row()[10], '123.456.78.9');
    }

}
