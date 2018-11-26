<?php
namespace LogHero\Client;


abstract class APIAccessBase implements APIAccessInterface {
    private $apiSettings;
    private $userAgent;

    public function __construct($clientId, APISettingsInterface $apiSettings) {
        $this->apiSettings = $apiSettings;
        $this->userAgent = $clientId . '; PHP SDK loghero/sdk@0.6.2';
    }

    public function submitLogPackage($payloadAsJson) {
        if ($this->apiSettings->getKey()) {
            $this->send($payloadAsJson);
        }
    }

    protected function getApiSettings() {
        return $this->apiSettings;
    }

    protected function getUserAgent() {
        return $this->userAgent;
    }

    abstract protected function send($payloadAsJson);
}
