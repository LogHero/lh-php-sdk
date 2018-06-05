<?php
namespace LogHero\Client;


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
