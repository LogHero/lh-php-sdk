<?php
namespace LogHero\Client;


interface LogBufferInterface {
    public function push($logEvent);
    public function needsDumping();
    public function dump();
}
