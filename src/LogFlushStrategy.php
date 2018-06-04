<?php
namespace LogHero\Client;


interface LogFlushStrategy {
    public function flush();
}


abstract class LogFlushStrategyBase implements LogFlushStrategy {
    protected $logBuffer;
    protected $apiAccess;

    public function __construct(LogBuffer $logBuffer, APIAccess $apiAccess) {
        $this->logBuffer = $logBuffer;
        $this->apiAccess = $apiAccess;
    }

    protected function dumpBufferAndSendLogsToApi() {
        $payload = $this->buildPayload($this->logBuffer->dump());
        $this->send($payload);
    }

    private function buildPayload(array $logEvents) {
        $rows = array();
        $columns = null;
        foreach ($logEvents as $logEvent) {
            try {
                $rows[] = $logEvent->row();
                if ($columns === null) {
                    $columns = $logEvent->columns();
                }
            }
            catch (\Exception $e) {
            }
        }
        assert($columns !== null);
        return array(
            'columns' => $columns,
            'rows' => $rows
        );
    }

    private function send($payload) {
        $this->apiAccess->submitLogPackage(json_encode($payload));
    }
}