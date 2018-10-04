<?php
namespace LogHero\Client;


class APISettings extends APISettingsDefault {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getKey() {
        return $this->apiKey;
    }
}
