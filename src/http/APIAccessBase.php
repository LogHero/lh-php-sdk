<?php
namespace LogHero\Client;


abstract class APIAccessBase implements APIAccessInterface {
    protected $apiKeyStorage;
    protected $apiLogPackageEndpoint;
    protected $userAgent;

    public function __construct(APIKeyStorageInterface $apiKeyStorage, $clientId, $apiLogPackageEndpoint='https://api.loghero.io/logs/') {
        $this->apiKeyStorage = $apiKeyStorage;
        $this->apiLogPackageEndpoint = $apiLogPackageEndpoint;
        $this->userAgent = $clientId . '; PHP SDK loghero/sdk@0.3.0';
    }

    public function submitLogPackage($payloadAsJson) {
        try {
            $this->send($payloadAsJson);
        }
        catch(APIKeyUndefinedException $e) {
        }
    }

    abstract protected function send($payloadAsJson);
}
