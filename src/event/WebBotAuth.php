<?php
namespace LogHero\Client;


/**
 * Collects the Web Bot Auth headers of a request (HTTP Message Signatures,
 * RFC 9421, profile draft-meunier-webbotauth-httpsig-protocol).
 *
 * The SDK does not verify anything. It only forwards what the Log Hero
 * backend needs to rebuild the signature base and verify the signature:
 * the Signature and Signature-Input headers plus the values of every regular
 * header the signature covers (Signature-Agent in practice). Derived
 * components such as @authority, @method or @path are not forwarded, the
 * backend rebuilds them from the hostname, protocol, method and landing page
 * columns that every log event already contains.
 *
 * Nothing is collected for requests without a Signature-Input header, which
 * covers practically all human visitors.
 */
class WebBotAuth {
    const MAX_VALUE_LENGTH = 4096;
    const MAX_COVERED_HEADERS = 16;

    // Headers that are never forwarded, even if a signature covers them.
    private static $blockedHeaders = array(
        'authorization',
        'proxy-authorization',
        'cookie',
        'set-cookie'
    );

    /**
     * @param array $server The $_SERVER array of the request.
     * @return array|null Null if the request is not signed.
     */
    public static function fromServer(array $server) {
        $signature = static::headerValue($server, 'signature');
        $signatureInput = static::headerValue($server, 'signature-input');
        if ($signature === null || $signatureInput === null) {
            return null;
        }
        $truncated = false;
        $headers = array();
        $coveredHeaders = static::coveredHeaderNames($signatureInput);
        if (!in_array('signature-agent', $coveredHeaders, true)) {
            // Needed for key discovery even if not signed.
            $coveredHeaders[] = 'signature-agent';
        }
        foreach ($coveredHeaders as $name) {
            if (count($headers) >= static::MAX_COVERED_HEADERS) {
                $truncated = true;
                break;
            }
            if (in_array($name, static::$blockedHeaders, true)) {
                continue;
            }
            $value = static::headerValue($server, $name);
            if ($value !== null) {
                $headers[$name] = $value;
            }
        }
        $result = array(
            'signature' => $signature,
            'signatureInput' => $signatureInput,
            'headers' => (object) $headers
        );
        if ($truncated) {
            $result['truncated'] = true;
        }
        return $result;
    }

    /**
     * Names of the regular (non derived) header components listed in all
     * signatures of a Signature-Input header, lower case and without
     * parameters. Example: sig1=("@authority" "signature-agent";key="sig1")
     * yields array('signature-agent').
     */
    public static function coveredHeaderNames($signatureInput) {
        $names = array();
        if (!preg_match_all('/\(([^)]*)\)/', $signatureInput, $innerLists)) {
            return $names;
        }
        foreach ($innerLists[1] as $innerList) {
            // Items only: a quoted string at the start or after whitespace.
            // Quoted parameter values (;key="sig1") follow a '=' and are skipped.
            if (!preg_match_all('/(?:^|\s)"([^"]+)"/', $innerList, $components)) {
                continue;
            }
            foreach ($components[1] as $component) {
                $name = strtolower(trim($component));
                if ($name === '' || $name[0] === '@') {
                    continue;
                }
                if (!preg_match('/^[a-z0-9!#$%&\'*+.^_`|~-]+$/', $name)) {
                    continue;
                }
                if (!in_array($name, $names, true)) {
                    $names[] = $name;
                }
            }
        }
        return $names;
    }

    private static function headerValue(array $server, $name) {
        $key = strtoupper(str_replace('-', '_', $name));
        if ($key !== 'CONTENT_TYPE' && $key !== 'CONTENT_LENGTH') {
            $key = 'HTTP_' . $key;
        }
        if (!array_key_exists($key, $server) || !is_string($server[$key])) {
            return null;
        }
        $value = trim($server[$key]);
        if ($value === '') {
            return null;
        }
        if (strlen($value) > static::MAX_VALUE_LENGTH) {
            $value = substr($value, 0, static::MAX_VALUE_LENGTH);
        }
        return $value;
    }
}
