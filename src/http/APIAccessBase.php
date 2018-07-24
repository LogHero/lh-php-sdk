<?php
namespace LogHero\Client;


abstract class APIAccessBase implements APIAccessInterface {
    protected $apiKeyStorage;
    protected $apiSettings;
    protected $userAgent;

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

    abstract protected function send($payloadAsJson);
}
