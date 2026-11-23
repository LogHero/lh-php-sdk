<?php
namespace LogHero\Client;


class LogEvent {
    protected $landingPagePath;
    protected $method;
    protected $statusCode;
    protected $timestamp;
    protected $pageLoadTimeMilliSec;
    protected $userAgent;
    protected $ipAddress;
    protected $hostname;
    protected $protocol;
    protected $referer;
    protected $webBotAuth;

    public function setHostname($hostname) {
        $this->hostname = $hostname;
        return $this;
    }

    public function setProtocol($protocol) {
        $this->protocol = $protocol;
        return $this;
    }

    public function setLandingPagePath($landingPagePath) {
        $this->landingPagePath = $landingPagePath;
        return $this;
    }

    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getUserAgent() {
        return $this->userAgent;
    }

    public function setReferer($referer) {
        $this->referer = $referer;
        return $this;
    }

    /**
     * @param array|null $webBotAuth Web Bot Auth headers, see WebBotAuth::fromServer().
     */
    public function setWebBotAuth($webBotAuth) {
        $this->webBotAuth = $webBotAuth;
        return $this;
    }

    public function getWebBotAuth() {
        return $this->webBotAuth;
    }

    public function setIpAddress($ipAddress) {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getTimestamp() {
        return $this->timestamp;
    }

    public function setPageLoadTimeMilliSec($pageLoadTimeMilliSec) {
        $this->pageLoadTimeMilliSec = $pageLoadTimeMilliSec;
        return $this;
    }

    public function columns() {
        return array(
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
            'referer',
            'webBotAuth',
            'clientIp'
        );
    }

    public function row() {
        $this->verify();
        return array(
            LogEvent::buildCidFromIPAndUserAgent($this->ipAddress, $this->userAgent),
            $this->hostname,
            $this->protocol,
            $this->landingPagePath,
            $this->method,
            $this->statusCode,
            $this->timestamp->format(\DateTime::ATOM),
            $this->pageLoadTimeMilliSec,
            hash('md5', $this->ipAddress),
            LogEvent::buildIpGroupHashes($this->ipAddress),
            $this->userAgent,
            $this->getExternalReferer(),
            $this->encodeWebBotAuth(),
            // Plain client IP, used by the Log Hero backend only to verify bots
            // (IP ranges, reverse DNS). Not to be confused with the legacy
            // rawIp column of DebugLogEvent, which is pushed to analytics.
            $this->ipAddress
        );
    }

    private function verify() {
        $this->ensureSet($this->landingPagePath, 'Landing page path');
        $this->ensureSet($this->userAgent, 'User agent');
        $this->ensureSet($this->ipAddress, 'Ip address');
        $this->ensureSet($this->hostname, 'Hostname');
        $this->ensureSet($this->timestamp, 'Timestamp');
    }

    private static function buildCidFromIPAndUserAgent($ipAddress, $userAgent) {
        return hash('md5', $ipAddress . $userAgent);
    }

    private static function buildIpGroupHashes($ipAddress) {
        $splitChar = filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? ':' : '.';
        $ipComponents = explode($splitChar, $ipAddress);
        if(count($ipComponents) < 4) {
            return null;
        }
        $ipComponentsHashed = [];
        foreach($ipComponents as $ipComponent) {
            $ipComponentsHashed[] = hash('md5', $ipComponent);
        }
        return implode($splitChar, $ipComponentsHashed);
    }

    private static function ensureSet($property, $propertyName) {
        if (!$property) {
            throw new InvalidLogEventException('Log event is incomplete: ' . $propertyName . ' is null');
        }
    }

    private function encodeWebBotAuth() {
        if (!$this->webBotAuth) {
            return null;
        }
        $encoded = json_encode($this->webBotAuth, JSON_UNESCAPED_SLASHES);
        return $encoded === false ? null : $encoded;
    }

    private function getExternalReferer() {
        if ($this->referer == 'direct') {
            return null;
        }
        return $this->referer;
    }
}
