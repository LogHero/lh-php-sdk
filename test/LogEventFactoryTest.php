<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\LogEventFactory;


class LogEventFactoryTest extends TestCase {
    private $logEventFactory;
    private $microtimeMock;
    private $expectedIpGroupHashes = 'ec5decca5ed3d6b8079e2e7e7bacc9f2.cfcd208495d565ef66e7dff9f98764da.cfcd208495d565ef66e7dff9f98764da.c4ca4238a0b923820dcc509a6f75849b';

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
            $this->expectedIpGroupHashes,
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
            $this->expectedIpGroupHashes,
            'Firefox',
            'https://www.loghero.io'
        ]);
    }

    public function testCreateLogEventWithIPv6() {
        $this->setupServerGlobal('/page-url');
        $_SERVER['REMOTE_ADDR'] = '2a00:8640:1::224:36ff:feef:1d89';
        $expected_ip_v6_group_hashes = '434c0622f0f867bef1e6fffbf057ba58:5aaffbae8a48fc24f114ee4dcd9c6171:c4ca4238a0b923820dcc509a6f75849b:d41d8cd98f00b204e9800998ecf8427e:13fe9d84310e77f13a6d184dbf1232f3:09f7a0c4a29ac689c226cfc35634bc54:2e838f2f2652dab5c976381d58463ba6:17e4193ffbe3375c8ec049e5760f1663';
        $logEvent = $this->logEventFactory->create();
        static::assertEquals($logEvent->row(), [
            'bc00fee03fa694306eb09fe604303372',
            'example.org',
            'http',
            '/page-url',
            'POST',
            301,
            '2018-04-11T06:48:18+00:00',
            2389,
            '935c68894fa1fecf4a5db560971a9427',
            $expected_ip_v6_group_hashes,
            'Firefox',
            'https://www.loghero.io'
        ]);
    }

    public function testCreateLogEventWithInvalidIp() {
        $this->setupServerGlobal('/page-url');
        $_SERVER['REMOTE_ADDR'] = '::';
        $logEvent = $this->logEventFactory->create();
        static::assertEquals($logEvent->row(), [
            'cd56639a5502ce8d383cb156402c849b',
            'example.org',
            'http',
            '/page-url',
            'POST',
            301,
            '2018-04-11T06:48:18+00:00',
            2389,
            '4501c091b0366d76ea3218b6cfdd8097',
            null,
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
            $this->expectedIpGroupHashes,
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
