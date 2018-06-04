<?php
namespace LogHero\Client;
require_once __DIR__ . '/APIAccess.php';
require_once __DIR__ . '/LogFlushStrategy.php';



class LogFlushStrategySync extends LogFlushStrategyBase {
    public function flush() {
        $this->dumpBufferAndSendLogsToApi();
    }
}