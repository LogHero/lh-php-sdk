<?php
namespace LogHero\Client;


class LogTransport extends DisabledLogTransport  {
    protected $apiAccess;

    public function __construct(LogBufferInterface $logBuffer, APIAccessInterface $apiAccess) {
        parent::__construct($logBuffer);
        $this->apiAccess = $apiAccess;
    }

    public function flush() {
        $payload = $this->buildPayload($this->logBuffer->dump());
        if ($payload['columns'] === null || count($payload['rows']) === 0) {
            return;
        }
        $this->send($payload);
    }

    private function buildPayload(array $logEvents) {
        $rows = array();
        $columns = null;
        foreach ($logEvents as $logEvent) {
            try {
                if ($columns === null) {
                    $columns = $logEvent->columns();
                }
                $rows[] = $logEvent->row();
            }
            catch (\Exception $e) {
            }
        }
        return array(
            'columns' => $columns,
            'rows' => $rows
        );
    }

    private function send($payload) {
        $this->apiAccess->submitLogPackage(json_encode($payload));
    }
}