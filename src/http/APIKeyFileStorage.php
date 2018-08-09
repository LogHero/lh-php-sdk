<?php
namespace LogHero\Client;


class APIKeyFileStorage implements APIKeyStorageInterface {
    private $keyStorageLocation;
    private $key;
    
    public function __construct($keyStorageLocation) {
        $this->keyStorageLocation = $keyStorageLocation;
    }

    public function setKey($apiKey) {
        FileLogBuffer::verifyWriteAccess($this->keyStorageLocation);
        file_put_contents($this->keyStorageLocation, $apiKey, LOCK_EX);
        chmod($this->keyStorageLocation, 0666);
    }
    
    public function getKey() {
        if ($this->key) {
            return $this->key;
        }
        if (!file_exists($this->keyStorageLocation)) {
            throw new APIKeyUndefinedException('Cannot read API key storage');
        }
        $this->key = file_get_contents($this->keyStorageLocation);
        if (!$this->key) {
            throw new APIKeyUndefinedException('API key storage is empty');
        }
        return $this->key;
    }
}
