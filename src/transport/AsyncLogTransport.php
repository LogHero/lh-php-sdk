<?php
namespace LogHero\Client;


class AsyncLogTransport extends LogTransport {
    private $clientId;
    private $authorizationToken;
    private $triggerEndpoint;

    public function __construct(
        LogBufferInterface $logBuffer,
        APIAccessInterface $apiAccess,
        $clientId,
        $authorizationToken,
        $triggerEndpoint
    ) {
        parent::__construct($logBuffer, $apiAccess);
        $this->clientId = $clientId;
        $this->authorizationToken = $authorizationToken;
        $this->triggerEndpoint = $triggerEndpoint;
    }

    public function flush() {
        try {
            $this->triggerAsyncFlush();
        }
        catch(APIAccessException $e) {
            $this->dumpLogEvents();
            throw new AsyncFlushFailedException($e);
        }
    }

    public function dumpLogEvents() {
        parent::flush();
    }

    private function triggerAsyncFlush() {
        $curlClient = $this->createCurlClient($this->triggerEndpoint);
        $curlClient->setOpt(CURLOPT_HTTPHEADER, array(
            'Token: '.$this->authorizationToken,
            'User-Agent: '.$this->clientId
        ));
        $curlClient->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        $curlClient->exec();
        $status = $curlClient->getInfo(CURLINFO_HTTP_CODE);
        if ( $status >= 300 ) {
            $errorMessage = $curlClient->error();
            $curlClient->close();
            throw new APIAccessException(
                'Call to URL '.$this->triggerEndpoint.' failed with status '.$status.'; Message: '.$errorMessage
            );
        }
        $curlClient->close();
    }

    protected function createCurlClient($url) {
        return new CurlClient($url);
    }
}