<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\WebBotAuth;


class WebBotAuthTest extends TestCase {
    private $signatureInput = 'sig1=("@authority" "signature-agent");created=1786951400;expires=1786951700;keyid="poqkLGiymh_W0uP6PZFw-dvez3QJT5SolqXBCW38r0U";alg="ed25519";tag="web-bot-auth"';

    public function testReturnsNullForUnsignedRequest() {
        static::assertNull(WebBotAuth::fromServer(array('HTTP_USER_AGENT' => 'Firefox')));
    }

    public function testReturnsNullIfSignatureInputIsMissing() {
        static::assertNull(WebBotAuth::fromServer(array('HTTP_SIGNATURE' => 'sig1=:abc=:')));
    }

    public function testCollectsSignatureHeaders() {
        $result = WebBotAuth::fromServer(array(
            'HTTP_SIGNATURE' => 'sig1=:abc=:',
            'HTTP_SIGNATURE_INPUT' => $this->signatureInput,
            'HTTP_SIGNATURE_AGENT' => '"https://chatgpt.com"'
        ));
        static::assertEquals('sig1=:abc=:', $result['signature']);
        static::assertEquals($this->signatureInput, $result['signatureInput']);
        static::assertEquals(array('signature-agent' => '"https://chatgpt.com"'), (array) $result['headers']);
        static::assertArrayNotHasKey('truncated', $result);
    }

    public function testForwardsAdditionalCoveredHeaders() {
        $result = WebBotAuth::fromServer(array(
            'HTTP_SIGNATURE' => 'sig1=:abc=:',
            'HTTP_SIGNATURE_INPUT' => 'sig1=("@authority" "@method" "signature-agent" "content-type" "x-intent");tag="web-bot-auth"',
            'HTTP_SIGNATURE_AGENT' => '"https://agent.example"',
            'CONTENT_TYPE' => 'text/html',
            'HTTP_X_INTENT' => 'browse'
        ));
        static::assertEquals(array(
            'signature-agent' => '"https://agent.example"',
            'content-type' => 'text/html',
            'x-intent' => 'browse'
        ), (array) $result['headers']);
    }

    public function testNeverForwardsCredentials() {
        $result = WebBotAuth::fromServer(array(
            'HTTP_SIGNATURE' => 'sig1=:abc=:',
            'HTTP_SIGNATURE_INPUT' => 'sig1=("@authority" "authorization" "cookie");tag="web-bot-auth"',
            'HTTP_AUTHORIZATION' => 'Bearer secret',
            'HTTP_COOKIE' => 'session=secret'
        ));
        static::assertEquals(array(), (array) $result['headers']);
    }

    public function testForwardsSignatureAgentEvenIfNotCovered() {
        $result = WebBotAuth::fromServer(array(
            'HTTP_SIGNATURE' => 'sig1=:abc=:',
            'HTTP_SIGNATURE_INPUT' => 'sig1=("@authority");tag="web-bot-auth"',
            'HTTP_SIGNATURE_AGENT' => '"https://chatgpt.com"'
        ));
        static::assertEquals(array('signature-agent' => '"https://chatgpt.com"'), (array) $result['headers']);
    }

    public function testParsesComponentParametersAndMultipleSignatures() {
        $names = WebBotAuth::coveredHeaderNames(
            'sig1=("@authority" "signature-agent";key="sig1");tag="web-bot-auth", sig2=("@path" "Signature-Agent" "x-foo");tag="web-bot-auth"'
        );
        static::assertEquals(array('signature-agent', 'x-foo'), $names);
    }

    public function testTruncatesLongValues() {
        $result = WebBotAuth::fromServer(array(
            'HTTP_SIGNATURE' => str_repeat('a', 5000),
            'HTTP_SIGNATURE_INPUT' => $this->signatureInput
        ));
        static::assertEquals(WebBotAuth::MAX_VALUE_LENGTH, strlen($result['signature']));
    }
}
