<?php
namespace LogHero\Client;


abstract class APIAccessBase implements APIAccessInterface {
    private $apiKeyStorage;
    private $apiSettings;
    private $userAgent;

    public function __construct(APIKeyStorageInterface $apiKeyStorage, $clientId, APISettingsInterface $apiSettings) {
        $this->apiKeyStorage = $apiKeyStorage;
        $this->apiSettings = $apiSettings;
        $this->userAgent = $clientId . '; PHP SDK loghero/sdk@0.4.0';
    }

    public function submitLogPackage($payloadAsJson) {
        try {
            $this->send($payloadAsJson);
        }
        catch(APIKeyUndefinedException $e) {
        }
    }

    protected function getApiKeyStorage() {
        return $this->apiKeyStorage;
    }

    protected function getApiSettings() {
        return $this->apiSettings;
    }

    protected function getUserAgent() {
        return $this->userAgent;
    }

    abstract protected function send($payloadAsJson);
}
