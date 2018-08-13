<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\DebugLogEvent;


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
        $this->assertEquals($logEvent->columns()[12], 'rawIp');
        $this->assertEquals($logEvent->row()[12], '123.456.78.9');
    }

}
