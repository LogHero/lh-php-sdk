<?php

    class LHLogEvent {
        var $landingPagePath;
        var $method;
        var $timestampAsIsoString;
        var $userAgent;
        var $ipAddress;

        function setCid($cid) {
            $this->cid = $cid;
        }

        function setLandingPagePath($landingPagePath) {
            $this->landingPagePath = $landingPagePath;
        }

        function setMethod($method) {
            $this->method = $method;
        }

        function setUserAgent($userAgent) {
            $this->userAgent = $userAgent;
        }

        function setIpAddress($ipAddress) {
            $this->ipAddress = $ipAddress;
        }

        function setTimestamp($timestamp) {
            $this->timestampAsIsoString = $timestamp->format(DateTime::ATOM);
        }

        private static function buildCidFromIPAndUserAgent($ipAddress, $userAgent) {
            return hash('md5', $ipAddress.$userAgent);
        }

        public static function columns() {
            return array(
                'cid',
                'landingPage',
                'method',
                'timestamp',
                'ip',
                'ua'
            );
        }

        public function row() {
            return array(
                LHLogEvent::buildCidFromIPAndUserAgent($this->ipAddress, $this->userAgent),
                $this->landingPagePath,
                $this->method,
                $this->timestampAsIsoString,
                hash('md5', $this->ipAddress),
                $this->userAgent
            );
        }

    }

    class LHClient {
        var $apiKey;
        var $logEventsPerRecord;
        var $logEvents = array();
        var $logEndpoint;

        public function __construct($apiKey, $logEventsPerRecord=25, $logEndpoint='http://test.t2ryddmw8p.eu-central-1.elasticbeanstalk.com/logs/') {
            $this->apiKey = $apiKey;
            $this->logEventsPerRecord = $logEventsPerRecord;
            $this->logEndpoint = $logEndpoint;
        }

        public function submit($logEvent) {
            array_push($this->logEvents, $logEvent);
            if (count($this->logEvents) >= $this->logEventsPerRecord) {
                $this->flush();
            }
        }

        public function flush() {
            print('Flushing '.count($this->logEvents)." records\n");
            $payload = $this->buildPayload();
            $this->send($payload);
            $this->logEvents = array();
        }

        private function buildPayload() {
            $rows = array();
            foreach ($this->logEvents as $logEvent) {
                array_push($rows, $logEvent->row());
            }
            return array(
                'columns' => LHLogEvent::columns(),
                'rows' => $rows
            );
        }

        private function send($payload) {
            $payloadAsJson = json_encode($payload);
            $curl = curl_init($this->logEndpoint);
            curl_setopt($curl, CURLOPT_HTTPHEADER,
                array(
                    'Content-type: application/json',
                    'Authorization: '.$this->apiKey
                )
            );
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payloadAsJson);
            $response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ( $status >= 300 ) {
                die('Error: call to URL $url failed with status $status, response $response, curl_error ' . curl_error($curl) . ', curl_errno ' . curl_errno($curl));
            }
            curl_close($curl);
        }

    }

?>
