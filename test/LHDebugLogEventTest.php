<?php
require_once __DIR__ . '/../src/LogHeroDebug.php';

use PHPUnit\Framework\TestCase;

class LHDebugLogEventTest extends TestCase {

    public function testSendsRawIpAddress() {
        $logEvent = new LHDebugLogEvent();
        $logEvent
            ->setIpAddress('123.456.78.9')
            ->setHostName('www.example.com')
            ->setLandingPagePath('/home')
            ->setMethod('GET')
            ->setStatusCode('200')
            ->setUserAgent('Firefox')
            ->setTimestamp(new DateTime('2018-03-31T15:03:01Z'));
        $this->assertEquals($logEvent->columns()[9], 'rawIp');
        $this->assertEquals($logEvent->row()[9], '123.456.78.9');
    }

}
