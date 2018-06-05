<?php
namespace LogHero\Client;


interface APIAccessInterface {
    public function submitLogPackage($payloadAsJson);
}
