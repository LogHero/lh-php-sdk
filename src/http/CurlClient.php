<?php
namespace LogHero\Client;


class CurlClient {
    private $curl;

    public function __construct($url) {
        $this->curl = curl_init($url);
    }

    public function setOpt($name, $value) {
        curl_setopt($this->curl, $name, $value);
    }

    public function exec() {
        return curl_exec($this->curl);
    }

    public function getInfo($infoType) {
        return curl_getinfo($this->curl, $infoType);
    }

    public function close() {
        curl_close($this->curl);
    }

    public function error() {
        return curl_error($this->curl);
    }
}
