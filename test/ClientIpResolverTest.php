<?php
namespace LogHero\Client\Test;

use PHPUnit\Framework\TestCase;
use LogHero\Client\ClientIpResolver;


class ClientIpResolverTest extends TestCase {
    private $googlebot = '66.249.66.1';

    public function testUsesRemoteAddrWithoutProxy() {
        $resolver = new ClientIpResolver();
        static::assertEquals('203.0.113.7', $resolver->resolve(array('REMOTE_ADDR' => '203.0.113.7')));
    }

    public function testIgnoresForwardingHeadersFromUnknownSources() {
        $resolver = new ClientIpResolver();
        static::assertEquals('203.0.113.7', $resolver->resolve(array(
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot,
            'HTTP_X_REAL_IP' => $this->googlebot,
            'HTTP_CF_CONNECTING_IP' => $this->googlebot,
        )));
    }

    public function testGoogleCloudLoadBalancerSkipsLoadBalancerAddress() {
        $resolver = new ClientIpResolver();
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '35.191.10.20',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot . ', 34.111.29.120',
        )));
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '130.211.1.5',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4, ' . $this->googlebot . ',34.111.29.120',
        )));
    }

    public function testGoogleCloudLoadBalancerWithoutHeaderFallsBackToRemoteAddr() {
        $resolver = new ClientIpResolver();
        static::assertEquals('35.191.10.20', $resolver->resolve(array('REMOTE_ADDR' => '35.191.10.20')));
    }

    public function testCloudflareConnectingIp() {
        $resolver = new ClientIpResolver();
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '172.70.1.2',
            'HTTP_CF_CONNECTING_IP' => $this->googlebot,
            'HTTP_X_FORWARDED_FOR' => '9.9.9.9',
        )));
        static::assertEquals('2001:db8::5', $resolver->resolve(array(
            'REMOTE_ADDR' => '2606:4700:10::1',
            'HTTP_CF_CONNECTING_IP' => '2001:db8::5',
        )));
    }

    public function testCloudflareWithoutConnectingIpUsesForwardedFor() {
        $resolver = new ClientIpResolver();
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '162.158.1.1',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot,
        )));
    }

    public function testLocalProxyReadsForwardedForRightToLeft() {
        $resolver = new ClientIpResolver();
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '6.6.6.6, ' . $this->googlebot . ', 10.0.0.3, 192.168.1.1',
        )));
    }

    public function testLocalProxyFallsBackToXRealIp() {
        $resolver = new ClientIpResolver();
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '10.1.2.3',
            'HTTP_X_REAL_IP' => $this->googlebot,
        )));
    }

    public function testLocalProxyWithoutHeaderUsesRemoteAddr() {
        $resolver = new ClientIpResolver();
        static::assertEquals('192.168.0.10', $resolver->resolve(array('REMOTE_ADDR' => '192.168.0.10')));
    }

    public function testCloudflareBehindGoogleLoadBalancer() {
        $resolver = new ClientIpResolver();
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '35.191.0.9',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot . ', 172.70.1.2, 34.111.29.120',
        )));
    }

    public function testInvalidEntryStopsChain() {
        $resolver = new ClientIpResolver();
        static::assertEquals('10.0.0.1', $resolver->resolve(array(
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot . ', unknown',
        )));
    }

    public function testStripsPortsAndMapsIpv4MappedAddresses() {
        $resolver = new ClientIpResolver();
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot . ':51234',
        )));
        static::assertEquals('2001:db8::1', $resolver->resolve(array(
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '[2001:db8::1]:443',
        )));
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '::ffff:' . $this->googlebot,
        )));
    }

    public function testAdditionalTrustedProxies() {
        $resolver = new ClientIpResolver(null, array('198.51.100.0/24', 'not-an-ip'));
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '198.51.100.20',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot,
        )));
        static::assertEquals('198.51.101.20', $resolver->resolve(array(
            'REMOTE_ADDR' => '198.51.101.20',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot,
        )));
    }

    public function testTrustedHeaderWithoutProxyListIsAlwaysUsed() {
        $resolver = new ClientIpResolver('True-Client-IP');
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_TRUE_CLIENT_IP' => $this->googlebot,
        )));
        static::assertEquals('203.0.113.7', $resolver->resolve(array('REMOTE_ADDR' => '203.0.113.7')));
    }

    public function testTrustedHeaderWithProxyListOnlyFromThoseProxies() {
        $resolver = new ClientIpResolver('HTTP_X_FORWARDED_FOR', array('203.0.113.7'));
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot,
        )));
        static::assertEquals('203.0.113.8', $resolver->resolve(array(
            'REMOTE_ADDR' => '203.0.113.8',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot,
        )));
    }

    public function testTrustedForwardedForBehindGoogleLoadBalancerStillSkipsLoadBalancer() {
        $resolver = new ClientIpResolver('X-Forwarded-For');
        static::assertEquals($this->googlebot, $resolver->resolve(array(
            'REMOTE_ADDR' => '35.191.10.20',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot . ',34.111.29.120',
        )));
    }

    public function testInvalidTrustedHeaderNameIsIgnored() {
        $resolver = new ClientIpResolver('X Forwarded: For');
        static::assertEquals('203.0.113.7', $resolver->resolve(array(
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_X_FORWARDED_FOR' => $this->googlebot,
        )));
    }

    public function testLegacyFallbackWithoutRemoteAddr() {
        $resolver = new ClientIpResolver();
        static::assertEquals('1.2.3.4', $resolver->resolve(array('HTTP_X_REAL_IP' => '1.2.3.4')));
        static::assertEquals('5.6.7.8', $resolver->resolve(array('SERVER_ADDR' => '5.6.7.8')));
        static::assertNull($resolver->resolve(array()));
    }

    public function testIsValidRange() {
        static::assertTrue(ClientIpResolver::isValidRange('10.0.0.0/8'));
        static::assertTrue(ClientIpResolver::isValidRange('2001:db8::/32'));
        static::assertTrue(ClientIpResolver::isValidRange('1.2.3.4'));
        static::assertFalse(ClientIpResolver::isValidRange('10.0.0.0/33'));
        static::assertFalse(ClientIpResolver::isValidRange('foo'));
        static::assertFalse(ClientIpResolver::isValidRange('10.0.0.0/x'));
    }
}
