<?php
namespace LogHero\Client;
require_once __DIR__ . '/CurlClient.php';


class APIAccessException extends \Exception {

}


interface APIAccessInterface {
    public function submitLogPackage($payloadAsJson);
}


abstract class APIAccessBase implements APIAccessInterface {
    protected $apiKey;
    protected $apiLogPackageEndpoint;
    protected $userAgent;

    public function __construct($apiKey, $clientId, $apiLogPackageEndpoint='https://api.loghero.io/logs/') {
        $this->apiKey = $apiKey;
        $this->apiLogPackageEndpoint = $apiLogPackageEndpoint;
        $this->userAgent = $clientId . '; PHP SDK loghero/sdk@0.2.2';
    }

    public function submitLogPackage($payloadAsJson) {
        if (empty($this->apiKey)) {
            return;
        }
        $this->send($payloadAsJson);
    }

    abstract protected function send($payloadAsJson);

}


class APIAccess extends APIAccessBase {

    protected function send($payloadAsJson) {
        $curlClient = $this->createCurlClient($this->apiLogPackageEndpoint);
        $curlClient->setOpt(CURLOPT_HTTPHEADER, array(
            'Content-type: application/json',
            'Content-encoding: deflate',
            'Authorization: '.$this->apiKey,
            'User-Agent: '.$this->userAgent
        ));
        $curlClient->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $curlClient->setOpt(CURLOPT_POSTFIELDS, gzcompress($payloadAsJson));
        $curlClient->exec();
        $status = $curlClient->getInfo(CURLINFO_HTTP_CODE);
        if ( $status >= 300 ) {
            $errorMessage = $curlClient->error();
            $curlClient->close();
            throw new APIAccessException(
                'Call to URL '.$this->apiLogPackageEndpoint.' failed with status '.$status.'; Message: '.$errorMessage
            );
        }
        $curlClient->close();
    }

    protected function createCurlClient($url) {
        return new CurlClient($url);
    }

}