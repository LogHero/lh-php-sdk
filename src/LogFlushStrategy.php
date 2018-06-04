<?php
namespace LogHero\Client;
require_once __DIR__ . '/LogBuffer.php';


interface LogFlushStrategy {
    public function flush(LogBuffer $logBuffer);
}

abstract class LogFlushStrategyBase implements LogFlushStrategy {
    
}