<?php
namespace LogHero\Client;


class APIAccess extends APIAccessBase {

    protected function send($payloadAsJson) {
        $apiLogPackageEndpoint = $this->getApiSettings()->getLogPackageEndpoint();
        $curlClient = $this->createCurlClient($apiLogPackageEndpoint);
        $curlClient->setOpt(CURLOPT_HTTPHEADER, array(
            'Content-type: application/json',
            'Content-encoding: deflate',
            'Authorization: '.$this->getApiSettings()->getKey(),
            'User-Agent: '.$this->getUserAgent()
        ));
        $curlClient->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $curlClient->setOpt(CURLOPT_POSTFIELDS, gzcompress($payloadAsJson));
        $curlClient->exec();
        $status = $curlClient->getInfo(CURLINFO_HTTP_CODE);
        if ( $status >= 300 ) {
            $errorMessage = $curlClient->error();
            $curlClient->close();
            throw new APIAccessException(
                'Call to URL '.$apiLogPackageEndpoint.' failed with status '.$status.'; Message: '.$errorMessage
            );
        }
        $curlClient->close();
    }

    protected function createCurlClient($url) {
        return new CurlClient($url);
    }

}