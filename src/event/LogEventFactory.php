<?php
namespace LogHero\Client;


class LogEventFactory {
    private static $ipAddressKeys = array(
        'REMOTE_ADDR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'SERVER_ADDR'
    );

    public function create() {
        $logEvent = new LogEvent();
        $this
            ->setHostname($logEvent)
            ->setProtocol($logEvent)
            ->setLandingPagePath($logEvent)
            ->setUserAgent($logEvent)
            ->setIpAddress($logEvent)
            ->setTimestampAndPageLoadTime($logEvent)
            ->setMethod($logEvent)
            ->setStatusCode($logEvent)
            ->setReferer($logEvent);
        return $logEvent;
    }

    private function setHostname($logEvent) {
        $logEvent->setHostname($_SERVER['HTTP_HOST']);
        return $this;
    }

    private function setProtocol($logEvent) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $logEvent->setProtocol($protocol);
        return $this;
    }

    private function setLandingPagePath($logEvent) {
        $logEvent->setLandingPagePath($_SERVER['REQUEST_URI']);
        return $this;
    }

    private function setMethod($logEvent) {
        $logEvent->setMethod(preg_replace('/[^\w]/', '', $_SERVER['REQUEST_METHOD']));
        return $this;
    }

    private function setStatusCode($logEvent) {
        if ( function_exists('http_response_code') ) {
            $logEvent->setStatusCode(http_response_code());
        }
        return $this;
    }

    private function setUserAgent($logEvent) {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
            $logEvent->setUserAgent($ua);
        }
        return $this;
    }

    private function setReferer($logEvent) {
        if (array_key_exists('HTTP_REFERER', $_SERVER)) {
            $logEvent->setReferer($_SERVER['HTTP_REFERER']);
        }
        return $this;
    }

    private function setTimestampAndPageLoadTime($logEvent) {
        $unixTimestamp = null;
        $pageLoadTimeMilliSec = null;
        if (!empty($_SERVER['REQUEST_TIME_FLOAT'])) { // PHP >= 5.4
            $unixTimestamp = $_SERVER['REQUEST_TIME_FLOAT'];
            $pageLoadTimeMilliSec = (int) (1000 * (microtime(true) - $unixTimestamp));
            $logEvent->setPageLoadTimeMilliSec($pageLoadTimeMilliSec);
        }
        else {
            $unixTimestamp = microtime(true);
        }
        $timeStamp = new \DateTime();
        $timeStamp->setTimestamp($unixTimestamp);
        $logEvent->setTimestamp($timeStamp);
        return $this;
    }

    private function setIpAddress($logEvent) {
        $ipAddress = null;
        foreach (static::$ipAddressKeys as $key) {
            $ipAddress = filter_var($_SERVER[$key], FILTER_VALIDATE_IP);
            if ($ipAddress) {
                break;
            }
        }
        $logEvent->setIpAddress($ipAddress);
        return $this;
    }
}