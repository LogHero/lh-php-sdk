#!/usr/bin/php -q
<?php
    include(dirname(__DIR__).'/src/LogHero.php');
    include(dirname(__DIR__).'/src/LogHeroDebug.php');

    date_default_timezone_set('Europe/Berlin');
    function randomDateString()
    {
        $intervalSeconds = 500;
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
        $logEvent = new LHDebugLogEvent();
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
        '79.228.13.104 - - ['.randomDateString().'] "GET /log-hero/themes/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (iPhone; CPU iPhone OS 10_3 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) CriOS/56.0.2924.75 Mobile/14E5239e Safari/602.1"',
        '79.228.13.104 - - ['.randomDateString().'] "GET /log-hero/articles/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (iPhone; CPU iPhone OS 10_3 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) CriOS/56.0.2924.75 Mobile/14E5239e Safari/602.1"',
        '76.344.23.424 - - ['.randomDateString().'] "GET /log-hero/themes/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Linux; Android 7.0; Pixel C Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36"',
        '76.344.23.424 - - ['.randomDateString().'] "GET /log-hero/articles/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Linux; Android 7.0; Pixel C Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36"',
        '89.36.206.3 - - ['.randomDateString().'] "GET /log-hero/articles/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246"',
        '89.36.206.3 - - ['.randomDateString().'] "GET /log-hero/plans/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246"',
        '76.344.23.424 - - ['.randomDateString().'] "GET /log-hero/themes/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246"',
        '79.228.13.104 - - ['.randomDateString().'] "GET /log-hero/plans/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Linux; Android 7.0; Pixel C Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36"',
         '57.123.48.399 - - ['.randomDateString().'] "GET /log-hero/plans/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246"',
        '91.45.15.109 - - ['.randomDateString().'] "GET /log-hero/plans/ HTTP/1.1" 302 - "-" "Mozilla/5.0 (iPhone; CPU iPhone OS 10_3 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) CriOS/56.0.2924.75 Mobile/14E5239e Safari/602.1"'
    );


    $logHero = new LHClient('YOUR_API_KEY', 3);
    foreach ($logString as $logString) {
        print('Submitting '.$logString."\n");
        $lhLogEvent = createLogEvent($logString, $logHero);
        $logHero->submit($lhLogEvent);
    }
    $logHero->flush();

?>
