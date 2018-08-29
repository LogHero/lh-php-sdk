<?php
namespace LogHero\Client;

use LogHero\Client\APISettingsDefault;


abstract class APISettings extends APISettingsDefault {
    private $apiKey;

    public function __construct($apiKey) {
        parent::__construct();
        $this->apiKey;
    }

    public function getKey() {
        return $this->apiKey;
    }
}
