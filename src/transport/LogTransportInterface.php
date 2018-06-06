<?php
namespace LogHero\Client;


interface LogTransportInterface {
    public function submit($logEvent);
    public function flush();
}
