<?php
namespace LogHero\Client;


interface StorageInterface {
    public function set($jsonDataAsString);
    public function get();
}
