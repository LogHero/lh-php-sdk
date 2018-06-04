<?php
namespace LogHero\Client;
require_once __DIR__ . '/../APIAccess.php';
require_once __DIR__ . '/LogTransport.php';


class AsyncLogTransport extends LogTransport {
    private $clientId;
    private $apiKey;
    private $triggerEndpoint;

    public function __construct(
        LogBuffer $logBuffer,
        APIAccess $apiAccess,
        $clientId,
        $apiKey,
        $triggerEndpoint
    ) {
        parent::__construct($logBuffer, $apiAccess);
        $this->clientId = $clientId;
        $this->apiKey = $apiKey;
        $this->triggerEndpoint = $triggerEndpoint;
    }

    public function flush() {
        $this->triggerAsyncFlush();
    }

    public function dumpLogEvents() {
        parent::flush();
    }

    private function triggerAsyncFlush() {
        $curlClient = $this->createCurlClient($this->triggerEndpoint);
        $curlClient->setOpt(CURLOPT_HTTPHEADER, array(
            'Authorization: '.$this->apiKey,
            'User-Agent: '.$this->clientId
        ));
        $curlClient->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        $curlClient->exec();
        $status = $curlClient->getInfo(CURLINFO_HTTP_CODE);
        if ( $status >= 300 ) {
            $errorMessage = $curlClient->error();
            $curlClient->close();
            throw new \LogHero\Client\APIAccessException(
                'Call to URL '.$this->triggerEndpoint.' failed with status '.$status.'; Message: '.$errorMessage
            );
        }
        $curlClient->close();
    }

    protected function createCurlClient($url) {
        return new CurlClient($url);
    }
}