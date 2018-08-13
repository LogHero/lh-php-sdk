<?php
namespace LogHero\Client\Test;

use LogHero\Client\LogEvent;


function createLogEvent($landingPagePath) {
    $logEvent = new LogEvent();
    $logEvent
        ->setUserAgent('Firefox')
        ->setIpAddress('123.45.67.89')
        ->setHostname('local.loghero.io')
        ->setProtocol('https')
        ->setLandingPagePath($landingPagePath)
        ->setMethod('GET')
        ->setStatusCode(200)
        ->setTimestamp(new \DateTime('2018-03-31T15:03:01Z'));
    return $logEvent;
}

function buildExpectedPayloadForLogEvents(array $logEvents) {
    $rows = array();
    foreach($logEvents as $logEvent) {
        $rows[] = $logEvent->row();
    }
    return json_encode(array(
        'columns' => [
            'cid',
            'hostname',
            'protocol',
            'landingPage',
            'method',
            'statusCode',
            'timestamp',
            'pageLoadTime',
            'ip',
            'ipGroups',
            'ua',
            'referer'
        ],
        'rows' => $rows
    ));
}
    
function assertLandingPagePathsInLogEvents($testCase, array $logEvents, array $landingPagePaths) {
    $testCase->assertEquals(count($logEvents), count($landingPagePaths));
    for ($i = 0, $numLogEvents = count($logEvents); $i < $numLogEvents; ++$i) {
        $testCase->assertEquals($logEvents[$i]->row()[3], $landingPagePaths[$i]);
    }
}
