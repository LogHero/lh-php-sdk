<?php
namespace LogHero\Client;


abstract class APISettingsDefault implements APISettingsInterface {

    public function getLogPackageEndpoint() {
        return 'https://api.loghero.io/logs/';
    }
}
