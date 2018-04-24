<?php
namespace LogHero\Client;
require_once __DIR__ . '/../src/LogEvent.php';


function createLogEvent($landingPagePath) {
    $logEvent = new LogEvent();
    $logEvent
        ->setUserAgent('Firefox')
        ->setIpAddress('123.45.67.89')
        ->setHostname('local.loghero.io')
        ->setLandingPagePath($landingPagePath)
        ->setMethod('GET')
        ->setStatusCode(200)
        ->setTimestamp(new \DateTime('2018-03-31T15:03:01Z'));
    return $logEvent;
}

function assertLandingPagePathsInLogEvents($testCase, $logEvents, $landingPagePaths) {
    $testCase->assertEquals(count($logEvents), count($landingPagePaths));
    for ($i = 0; $i < count($logEvents); ++$i) {
        $testCase->assertEquals($logEvents[$i]->row()[2], $landingPagePaths[$i]);
    }
}
