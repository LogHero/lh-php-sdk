<?php
namespace LogHero\Client;


class APIKeyMemStorage implements APIKeyStorageInterface {
    private $key;
    
    public function setKey($apiKey) {
        $this->key = $apiKey;
    }

    public function getKey() {
        if (!$this->key) {
            throw new APIKeyUndefinedException('API key storage is empty');
        }
        return $this->key;
    }
}
