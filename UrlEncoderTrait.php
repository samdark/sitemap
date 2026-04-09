<?php
namespace samdark\sitemap;

/**
 * Provides URL encoding functionality for sitemap classes.
 * Percent-encodes non-ASCII characters in URL components per RFC 3986
 * while preserving existing percent-encoded sequences to avoid double-encoding.
 */
trait UrlEncoderTrait
{
    /**
     * Encodes a URL to ensure international characters are properly percent-encoded
     * according to RFC 3986 while avoiding double-encoding of existing %HH sequences.
     *
     * @param string $url the URL to encode
     * @return string the encoded URL
     */
    protected function encodeUrl($url)
    {
        $parsed = parse_url($url);

        if ($parsed === false) {
            return $url;
        }

        $encoded = '';

        // Scheme (http, https, etc.)
        if (isset($parsed['scheme'])) {
            $encoded .= $parsed['scheme'] . '://';
        }

        // User info (credentials)
        if (isset($parsed['user'])) {
            $encoded .= $parsed['user'];
            if (isset($parsed['pass'])) {
                $encoded .= ':' . $parsed['pass'];
            }
            $encoded .= '@';
        }

        // Host (domain)
        if (isset($parsed['host'])) {
            if (function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46')) {
                $host = idn_to_ascii($parsed['host'], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
                $encoded .= $host !== false ? $host : $parsed['host'];
            } else {
                $encoded .= $parsed['host'];
            }
        }

        // Port
        if (isset($parsed['port'])) {
            $encoded .= ':' . $parsed['port'];
        }

        // Path — encode only non-ASCII bytes; existing %HH sequences are ASCII and are preserved
        if (isset($parsed['path'])) {
            $encoded .= $this->encodeNonAscii($parsed['path']);
        }

        // Query string — encode only non-ASCII bytes in each key and value
        if (isset($parsed['query'])) {
            $parts = explode('&', $parsed['query']);
            $encodedParts = array();
            foreach ($parts as $part) {
                if (strpos($part, '=') !== false) {
                    list($key, $value) = explode('=', $part, 2);
                    $encodedParts[] = $this->encodeNonAscii($key) . '=' . $this->encodeNonAscii($value);
                } else {
                    $encodedParts[] = $this->encodeNonAscii($part);
                }
            }
            $encoded .= '?' . implode('&', $encodedParts);
        }

        // Fragment
        if (isset($parsed['fragment'])) {
            $encoded .= '#' . $this->encodeNonAscii($parsed['fragment']);
        }

        return $encoded;
    }

    /**
     * Percent-encodes sequences of non-ASCII bytes in a string while leaving
     * all ASCII characters (including existing %HH sequences) untouched.
     *
     * @param string $value the string to encode
     * @return string
     */
    private function encodeNonAscii($value)
    {
        return preg_replace_callback(
            '/[^\x00-\x7F]+/',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $value
        );
    }
}
