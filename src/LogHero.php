<?php
require_once(dirname(__FILE__).'/APIAccess.php');

class LHLogEvent {
    protected $landingPagePath;
    protected $method;
    protected $statusCode;
    protected $timestampAsIsoString;
    protected $userAgent;
    protected $ipAddress;
    protected $hostname;

    function setHostname($hostname) {
        $this->hostname = $hostname;
        return $this;
    }

    function setLandingPagePath($landingPagePath) {
        $this->landingPagePath = $landingPagePath;
        return $this;
    }

    function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;
        return $this;
    }

    function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
        return $this;
    }

    function setIpAddress($ipAddress) {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    function setTimestamp($timestamp) {
        $this->timestampAsIsoString = $timestamp->format(DateTime::ATOM);
        return $this;
    }

    private static function buildCidFromIPAndUserAgent($ipAddress, $userAgent) {
        return hash('md5', $ipAddress.$userAgent);
    }

    public function columns() {
        return array(
            'cid',
            'hostname',
            'landingPage',
            'method',
            'statusCode',
            'timestamp',
            'ip',
            'ua'
        );
    }

    # TODO Verify row data before sending to backend
    public function row() {
        return array(
            LHLogEvent::buildCidFromIPAndUserAgent($this->ipAddress, $this->userAgent),
            $this->hostname,
            $this->landingPagePath,
            $this->method,
            $this->statusCode,
            $this->timestampAsIsoString,
            hash('md5', $this->ipAddress),
            $this->userAgent
        );
    }

}

class LHClient {
    private $apiAccess;
    private $logEventsPerRecord = 25;
    private $logEvents = array();

    public function __construct($apiAccess) {
        $this->apiAccess = $apiAccess;
    }

    public static function create($apiKey, $logEndpoint='http://test.t2ryddmw8p.eu-central-1.elasticbeanstalk.com/logs/') {
        return new LHClient(new APIAccessCurl($apiKey, $logEndpoint));
    }

    public function submit($logEvent) {
        array_push($this->logEvents, $logEvent);
        if (count($this->logEvents) >= $this->logEventsPerRecord) {
            $this->flush();
        }
    }

    public function flush() {
        if (count($this->logEvents) == 0) {
            print('Ignore flush because no log events are recorded\n');
            return;
        }
        $payload = $this->buildPayload();
        $this->send($payload);
        $this->logEvents = array();
    }

    private function buildPayload() {
        $rows = array();
        $columns = NULL;
        foreach ($this->logEvents as $logEvent) {
            array_push($rows, $logEvent->row());
            if (is_null($columns)) {
                $columns = $logEvent->columns();
            }
        }
        assert(is_null($columns) == false);
        return array(
            'columns' => $columns,
            'rows' => $rows
        );
    }

    private function send($payload) {
        $this->apiAccess->submitLogPackage(json_encode($payload));
    }

}

