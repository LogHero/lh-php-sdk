<?php
namespace LogHero\Client;

interface APIAccess {
    public function submitLogPackage($payloadAsJson);
}

class APIAccessException extends \Exception {
    
}

abstract class APIAccessBase implements APIAccess {
    protected $apiKey;
    protected $apiLogPackageEndpoint;

    public function __construct($apiKey, $clientId, $apiLogPackageEndpoint) {
        $this->apiKey = $apiKey;
        $this->apiLogPackageEndpoint = $apiLogPackageEndpoint;
        $this->userAgent = $clientId . '; PHP SDK loghero/sdk@0.2.1';
    }

    public function submitLogPackage($payloadAsJson) {
        if (empty($this->apiKey)) {
            return;
        }
        $this->send($payloadAsJson);
    }

    abstract protected function send($payloadAsJson);

}


class CurlClient {
    private $curl;

    public function __construct($url) {
        $this->curl = curl_init($url);
    }

    public function setOpt($name, $value) {
        curl_setopt($this->curl, $name, $value);
    }

    public function exec() {
        return curl_exec($this->curl);
    }

    public function getInfo($infoType) {
        return curl_getinfo($this->curl, $infoType);
    }

    public function close() {
        curl_close($this->curl);
    }
    
    public function error() {
        return curl_error($this->curl);
    }
}


class APIAccessCurl extends APIAccessBase {

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