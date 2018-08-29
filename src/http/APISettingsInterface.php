<?php
namespace LogHero\Client;


interface APISettingsInterface {
    public function getKey();
    public function getLogPackageEndpoint();
}
