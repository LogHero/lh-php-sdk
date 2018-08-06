<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\LogEventFactory;


class LogEventFactoryTest extends TestCase {
    private $logEventFactory;
    private $microtimeMock;

    public function setUp() {
        parent::setUp();
        $GLOBALS['currentTime'] = 1523429300.8000;
        $this->logEventFactory = new LogEventFactory();
        $this->microtimeMock = createMicrotimeMock();
        $this->microtimeMock->enable();
    }

    public function tearDown() {
        parent::tearDown();
        $this->microtimeMock->disable();
    }

    public function testCreateLogEvent() {
        $this->setupServerGlobal('/page-url');
        $logEvent = $this->logEventFactory->create();
        static::assertEquals($logEvent->row(), [
            'd113ff3141723d50fec2933977c89ea6',
            'example.org',
            'http',
            '/page-url',
            'POST',
            301,
            '2018-04-11T06:48:18+00:00',
            2389,
            'f528764d624db129b32c21fbca0cb8d6',
            'Firefox',
            'https://www.loghero.io'
        ]);
    }

    public function testCreateLogEventWithoutPageLoadTimeIfNoRequestTime() {
        $this->setupServerGlobal('/page-url');
        $_SERVER['REQUEST_TIME_FLOAT'] = null;
        $logEvent = $this->logEventFactory->create();
        static::assertEquals($logEvent->row(), [
            'd113ff3141723d50fec2933977c89ea6',
            'example.org',
            'http',
            '/page-url',
            'POST',
            301,
            '2018-04-11T06:48:20+00:00',
            null,
            'f528764d624db129b32c21fbca0cb8d6',
            'Firefox',
            'https://www.loghero.io'
        ]);
    }

    public function testHandleRefererNotSet() {
        $this->setupServerGlobal('/page-url');
        unset($_SERVER['HTTP_REFERER']);
        $logEvent = $this->logEventFactory->create();
        static::assertEquals($logEvent->row(), [
            'd113ff3141723d50fec2933977c89ea6',
            'example.org',
            'http',
            '/page-url',
            'POST',
            301,
            '2018-04-11T06:48:18+00:00',
            2389,
            'f528764d624db129b32c21fbca0cb8d6',
            'Firefox',
            null
        ]);
    }

    public function testSetHttpsProtocol() {
        $protocolColumnIdx = 2;
        $this->setupServerGlobal('/page-url');
        $_SERVER['HTTPS'] = true;
        static::assertEquals($this->logEventFactory->create()->row()[$protocolColumnIdx], 'https');
        $_SERVER['HTTPS'] = 'off';
        static::assertEquals($this->logEventFactory->create()->row()[$protocolColumnIdx], 'http');
    }
    
    private function setupServerGlobal($pageUrl) {
        $_SERVER['REQUEST_URI'] = $pageUrl;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_USER_AGENT'] = 'Firefox';
        $_SERVER['REQUEST_TIME_FLOAT'] = 1523429298.4109;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_REFERER'] = 'https://www.loghero.io';
        $_SERVER['HTTP_HOST'] = 'example.org';
        unset($_SERVER['HTTPS']);
        http_response_code(301);
    }
}
