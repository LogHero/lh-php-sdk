<?php
namespace LogHero\Client;


class ClientIpResolver {
    private static $googleCloudLoadBalancerRanges = array(
        '35.191.0.0/16',
        '130.211.0.0/22',
    );

    private static $cloudflareRanges = array(
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    );

    private static $privateRanges = array(
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
        '100.64.0.0/10',
        '169.254.0.0/16',
        '::1/128',
        'fc00::/7',
        'fe80::/10',
    );

    private static $legacyFallbackKeys = array(
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'SERVER_ADDR',
    );

    private $trustedHeaderKey;
    private $trustedProxies;

    public function __construct($trustedHeader = null, $trustedProxies = array()) {
        $this->trustedHeaderKey = static::toServerKey($trustedHeader);
        $this->trustedProxies = array();
        foreach ((array) $trustedProxies as $proxy) {
            if (static::isValidRange($proxy)) {
                $this->trustedProxies[] = trim($proxy);
            }
        }
    }

    public function resolve($server) {
        $remoteAddr = static::validIp(static::get($server, 'REMOTE_ADDR'));
        if ($remoteAddr === null) {
            return static::legacyFallback($server);
        }
        $fromGoogleLb = static::inRanges($remoteAddr, static::$googleCloudLoadBalancerRanges);
        if ($this->trustedHeaderKey !== null) {
            $fromTrustedProxy = empty($this->trustedProxies) || $this->isKnownProxy($remoteAddr);
            if ($fromTrustedProxy) {
                $skipTrailing = ($fromGoogleLb && $this->trustedHeaderKey === 'HTTP_X_FORWARDED_FOR') ? 1 : 0;
                $ip = $this->fromHeader(static::get($server, $this->trustedHeaderKey), $skipTrailing);
                if ($ip !== null) {
                    return $ip;
                }
            }
        }
        if (static::inRanges($remoteAddr, static::$cloudflareRanges)) {
            $ip = static::validIp(static::get($server, 'HTTP_CF_CONNECTING_IP'));
            if ($ip !== null) {
                return $ip;
            }
        }
        if ($fromGoogleLb) {
            $ip = $this->fromHeader(static::get($server, 'HTTP_X_FORWARDED_FOR'), 1);
            return $ip !== null ? $ip : $remoteAddr;
        }
        if ($this->isKnownProxy($remoteAddr)) {
            $ip = $this->fromHeader(static::get($server, 'HTTP_X_FORWARDED_FOR'), 0);
            if ($ip === null) {
                $ip = $this->fromHeader(static::get($server, 'HTTP_X_REAL_IP'), 0);
            }
            if ($ip !== null) {
                return $ip;
            }
        }
        return $remoteAddr;
    }

    private function fromHeader($value, $skipTrailing) {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $entries = array_reverse(explode(',', $value));
        foreach (array_slice($entries, $skipTrailing) as $entry) {
            $ip = static::validIp(static::stripPort(trim($entry)));
            if ($ip === null) {
                return null;
            }
            if (!$this->isKnownProxy($ip)) {
                return $ip;
            }
        }
        return null;
    }

    private function isKnownProxy($ip) {
        return static::inRanges($ip, static::$privateRanges)
            || static::inRanges($ip, static::$cloudflareRanges)
            || static::inRanges($ip, static::$googleCloudLoadBalancerRanges)
            || static::inRanges($ip, $this->trustedProxies);
    }

    private static function legacyFallback($server) {
        foreach (static::$legacyFallbackKeys as $key) {
            $ip = static::validIp(static::get($server, $key));
            if ($ip !== null) {
                return $ip;
            }
        }
        return null;
    }

    private static function get($server, $key) {
        return (is_array($server) && array_key_exists($key, $server)) ? $server[$key] : null;
    }

    private static function validIp($value) {
        if (!is_string($value)) {
            return null;
        }
        $ip = filter_var(trim($value), FILTER_VALIDATE_IP);
        if ($ip === false) {
            return null;
        }
        if (stripos($ip, '::ffff:') === 0) {
            $v4 = filter_var(substr($ip, 7), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            if ($v4 !== false) {
                return $v4;
            }
        }
        return $ip;
    }

    private static function stripPort($entry) {
        if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $entry, $m)) {
            return $m[1];
        }
        if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $entry, $m)) {
            return $m[1];
        }
        return $entry;
    }

    private static function toServerKey($header) {
        if (!is_string($header)) {
            return null;
        }
        $header = trim($header);
        if ($header === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $header)) {
            return null;
        }
        $key = strtoupper(str_replace('-', '_', $header));
        if (strpos($key, 'HTTP_') !== 0) {
            $key = 'HTTP_' . $key;
        }
        return $key;
    }

    public static function isValidRange($range) {
        if (!is_string($range)) {
            return false;
        }
        $parts = explode('/', trim($range), 2);
        $packed = @inet_pton($parts[0]);
        if ($packed === false) {
            return false;
        }
        if (count($parts) === 1) {
            return true;
        }
        $maxBits = strlen($packed) * 8;
        return ctype_digit($parts[1]) && (int) $parts[1] <= $maxBits;
    }

    private static function inRanges($ip, $ranges) {
        $packedIp = @inet_pton($ip);
        if ($packedIp === false) {
            return false;
        }
        foreach ($ranges as $range) {
            if (static::inRange($packedIp, $range)) {
                return true;
            }
        }
        return false;
    }

    private static function inRange($packedIp, $range) {
        $parts = explode('/', $range, 2);
        $packedNet = @inet_pton($parts[0]);
        if ($packedNet === false || strlen($packedNet) !== strlen($packedIp)) {
            return false;
        }
        $bits = count($parts) === 2 ? (int) $parts[1] : strlen($packedNet) * 8;
        $fullBytes = intdiv($bits, 8);
        if (substr($packedIp, 0, $fullBytes) !== substr($packedNet, 0, $fullBytes)) {
            return false;
        }
        $remainingBits = $bits % 8;
        if ($remainingBits === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($packedIp[$fullBytes]) & $mask) === (ord($packedNet[$fullBytes]) & $mask);
    }
}
