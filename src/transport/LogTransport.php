<?php
namespace LogHero\Client;


class LogTransport implements LogTransportInterface {
    protected $logBuffer;
    protected $apiAccess;

    public function __construct(LogBufferInterface $logBuffer, APIAccessInterface $apiAccess) {
        $this->logBuffer = $logBuffer;
        $this->apiAccess = $apiAccess;
    }

    public function submit($logEvent) {
        $this->logBuffer->push($logEvent);
        if ($this->logBuffer->needsDumping()) {
            $this->flush();
        }
    }

    public function flush() {
        $payload = $this->buildPayload($this->logBuffer->dump());
        if ($payload['columns'] === null or count($payload['rows']) === 0) {
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