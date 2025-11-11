<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeUrl implements ValidationRule
{
    /**
     * Private IP ranges that should be blocked to prevent SSRF attacks.
     */
    private const BLOCKED_IP_RANGES = [
        '127.0.0.0/8',      // localhost
        '10.0.0.0/8',      // private
        '172.16.0.0/12',   // private
        '192.168.0.0/16',  // private
        '169.254.0.0/16',  // link-local
        '::1/128',         // IPv6 localhost
        'fc00::/7',        // IPv6 private
        'fe80::/10',       // IPv6 link-local
    ];

    /**
     * Blocked hostnames that could be used for SSRF.
     */
    private const BLOCKED_HOSTNAMES = [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        'metadata.google.internal',
        'metadata.azure.com',
        '169.254.169.254', // AWS metadata
    ];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid URL string.');

            return;
        }

        $value = trim($value);

        // Check for invalid schemes BEFORE normalizing
        $parsedBeforeNormalize = parse_url($value);
        if ($parsedBeforeNormalize !== false && isset($parsedBeforeNormalize['scheme'])) {
            $scheme = strtolower($parsedBeforeNormalize['scheme']);
            if (! in_array($scheme, ['http', 'https'])) {
                $fail('The :attribute must use http or https protocol.');

                return;
            }
        }

        // Normalize URL by adding https:// if no scheme is provided
        $url = $this->normalizeUrl($value);

        // Validate URL format
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        $parsed = parse_url($url);

        // Check if URL can be parsed and has a host
        if ($parsed === false || ! isset($parsed['host'])) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        // Only allow http and https schemes (double check after normalization)
        if (! isset($parsed['scheme']) || ! in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
            $fail('The :attribute must use http or https protocol.');

            return;
        }

        // Check for blocked hostnames
        $host = $parsed['host'] ?? null;
        if (! $host) {
            $fail('The :attribute must include a valid hostname.');

            return;
        }

        $hostLower = strtolower($host);

        // Check against blocked hostname list FIRST (before domain format validation)
        foreach (self::BLOCKED_HOSTNAMES as $blocked) {
            if ($hostLower === $blocked || str_ends_with($hostLower, '.'.$blocked)) {
                $fail('The :attribute cannot point to internal or private addresses.');

                return;
            }
        }

        // Basic validation: host should look like a domain (contain dots) or be a valid IP
        // This catches cases like "not-a-url" which becomes "https://not-a-url"
        if (! filter_var($host, FILTER_VALIDATE_IP) && ! preg_match('/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $host)) {
            $fail('The :attribute must be a valid URL with a proper domain name or IP address.');

            return;
        }

        // Resolve hostname to IP address
        $ip = gethostbyname($hostLower);

        // If resolution failed or returned the hostname itself, it might be invalid
        if ($ip === $hostLower && ! filter_var($hostLower, FILTER_VALIDATE_IP)) {
            // This could be a valid domain that just doesn't resolve, so we'll allow it
            // but the HTTP request will fail anyway
            return;
        }

        // Check if IP is in blocked ranges
        if ($this->isIpBlocked($ip)) {
            $fail('The :attribute cannot point to internal or private addresses.');

            return;
        }
    }

    /**
     * Normalize URL by adding https:// if no scheme is provided.
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        // Remove leading/trailing whitespace and slashes
        $url = trim($url, '/');

        // If no scheme, add https://
        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
    }

    /**
     * Check if an IP address is in any of the blocked ranges.
     */
    private function isIpBlocked(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        foreach (self::BLOCKED_IP_RANGES as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address is within a CIDR range.
     */
    private function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $mask] = explode('/', $range);
        $maskInt = (int) $mask;

        // Validate mask
        if ($maskInt < 0 || $maskInt > 128) {
            return false;
        }

        // Handle IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($maskInt > 128) {
                return false;
            }

            $ipLong = inet_pton($ip);
            $subnetLong = inet_pton($subnet);
            if ($ipLong === false || $subnetLong === false) {
                return false;
            }

            // Create mask
            $maskBinary = str_repeat('1', $maskInt).str_repeat('0', 128 - $maskInt);
            $maskPacked = '';
            for ($i = 0; $i < 16; $i++) {
                $byte = bindec(substr($maskBinary, $i * 8, 8));
                $maskPacked .= chr($byte);
            }

            return ($ipLong & $maskPacked) === ($subnetLong & $maskPacked);
        }

        // Handle IPv4
        if ($maskInt > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        // Only calculate mask if mask is valid (not 0)
        if ($maskInt === 0) {
            return true; // /0 matches everything
        }

        $maskLong = -1 << (32 - $maskInt);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
