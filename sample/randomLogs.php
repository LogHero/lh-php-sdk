#!/usr/bin/php -q
<?php
    include(dirname(__DIR__).'/src/LogHero.php');

    date_default_timezone_set('Europe/Berlin');
    function randomDateString()
    {
        $intervalSeconds = 3600;
        $startDate = new DateTime();
        $startDate->sub(new DateInterval('PT'.$intervalSeconds.'S'));
        $endDate = new DateTime();
        $newDateTimestamp = mt_rand($startDate->getTimestamp(), $endDate->getTimestamp());
        $randomDate = new DateTime();
        $randomDate->setTimestamp($newDateTimestamp);
        return $randomDate->format('d/M/Y:H:i:s');
    }


    function createLogEvent($logString) {
        $logElementsSpaces = explode(' ', $logString);
        $logElementsQuotes = explode('"', $logString);
        $logEvent = new LHLogEvent();
        $userAgent = $logElementsQuotes[5];
        $logEvent->setUserAgent($userAgent);
        $logEvent->setIpAddress($logElementsSpaces[0]);
        $logEvent->setLandingPagePath($logElementsSpaces[5]);
        $logEvent->setMethod($logElementsSpaces[4]);
        $timestampAsString = $logElementsSpaces[3];
        $timestampAsString = str_replace('[', '', $timestampAsString);
        $timestampAsString = str_replace(']', '', $timestampAsString);
        $timestamp = DateTime::createFromFormat('d/M/Y:H:i:s', $timestampAsString);
        $logEvent->setTimestamp($timestamp);
        return $logEvent;
    }


    $logString = array(
        '79.228.13.104 - - ['.randomDateString().'] "GET /log-hero/themes/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36"',
        '79.228.13.104 - - ['.randomDateString().'] "GET /log-hero/articles/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36"',
        '76.344.23.424 - - ['.randomDateString().'] "GET /log-hero/themes/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36"',
        '76.344.23.424 - - ['.randomDateString().'] "GET /log-hero/articles/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36"',
        '89.36.206.3 - - ['.randomDateString().'] "GET /log-hero/articles/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36"',
        '89.36.206.3 - - ['.randomDateString().'] "GET /log-hero/plans/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36"',
        '76.344.23.424 - - ['.randomDateString().'] "GET /log-hero/themes/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36"',
        '79.228.13.104 - - ['.randomDateString().'] "GET /log-hero/plans/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36"',
        '57.123.48.399 - - ['.randomDateString().'] "GET /log-hero/plans/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36"',
        '91.45.15.109 - - ['.randomDateString().'] "GET /log-hero/plans/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36"'
    );


    $logHero = new LHClient('YOUR_API_KEY', 3);
    foreach ($logString as $logString) {
        print('Submitting '.$logString."\n");
        $lhLogEvent = createLogEvent($logString, $logHero);
        $logHero->submit($lhLogEvent);
    }
    $logHero->flush();

?>
