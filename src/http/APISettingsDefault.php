<?php
namespace LogHero\Client;


class APISettingsDefault implements APISettingsInterface {

    public function getAPILogPackageEndpoint() {
        return 'https://api.loghero.io/logs/';
    }
}
