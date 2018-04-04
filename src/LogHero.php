<?php

    class LHLogEvent {
        protected $landingPagePath;
        protected $method;
        protected $statusCode;
        protected $timestampAsIsoString;
        protected $userAgent;
        protected $ipAddress;

        function setCid($cid) {
            $this->cid = $cid;
        }

        function setLandingPagePath($landingPagePath) {
            $this->landingPagePath = $landingPagePath;
        }

        function setMethod($method) {
            $this->method = $method;
        }
        
        function setStatusCode($statusCode) {
            $this->statusCode = $statusCode;
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

        public function columns() {
            return array(
                'cid',
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
        private $apiKey;
        private $logEventsPerRecord;
        private $logEvents = array();
        private $logEndpoint;

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
