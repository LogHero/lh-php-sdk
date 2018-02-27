<?php

    class LHLogEvent {
        var $ipAdress;

        function setIpAddress($ipAdress) {
            $this->ipAdress = $ipAdress;
        }

        public static function columns() {
            return array('IP');
        }

        public function row() {
            return array(
                $this->ipAdress
            );
        }

    }

    class LHClient {
        var $apiKey;
        var $logEventsPerRecord;
        var $logEvents = array();
        var $logEndpoint;

        public function __construct($apiKey, $logEventsPerRecord=25, $logEndpoint='http://localhost:8081/logs/') {
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
            print('Flusing '.count($this->logEvents)." records\n");
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
