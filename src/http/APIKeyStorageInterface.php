<?php
namespace LogHero\Client;


interface APIKeyStorageInterface {
    public function setKey($apiKey);
    public function getKey();
}
