<?php
namespace LogHero\Client;


class DisabledLogTransport implements LogTransportInterface {
    protected $logBuffer;

    public function __construct(LogBufferInterface $logBuffer) {
        $this->logBuffer = $logBuffer;
    }

    public function submit($logEvent) {
        $this->logBuffer->push($logEvent);
        if ($this->logBuffer->needsDumping()) {
            $this->flush();
        }
    }

    public function flush() {
    }
}