<?php

namespace Tests\Feature;

use App\Rules\SafeUrl;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SafeUrlRuleTest extends TestCase
{
    /**
     * Test that valid public URLs pass validation.
     */
    public function test_validates_public_urls(): void
    {
        $rule = new SafeUrl;
        $validator = Validator::make(
            ['url' => 'https://laravel.com'],
            ['url' => ['required', $rule]]
        );

        $this->assertTrue($validator->passes());
    }

    /**
     * Test that URLs without scheme are normalized and pass.
     */
    public function test_validates_urls_without_scheme(): void
    {
        $rule = new SafeUrl;
        $validator = Validator::make(
            ['url' => 'laravel.com'],
            ['url' => ['required', $rule]]
        );

        $this->assertTrue($validator->passes());
    }

    /**
     * Test that localhost is blocked.
     */
    public function test_blocks_localhost(): void
    {
        $rule = new SafeUrl;
        $validator = Validator::make(
            ['url' => 'http://localhost'],
            ['url' => ['required', $rule]]
        );

        $this->assertFalse($validator->passes());
        $this->assertStringContainsString('internal or private', $validator->errors()->first('url'));
    }

    /**
     * Test that 127.0.0.1 is blocked.
     */
    public function test_blocks_127_0_0_1(): void
    {
        $rule = new SafeUrl;
        $validator = Validator::make(
            ['url' => 'http://127.0.0.1'],
            ['url' => ['required', $rule]]
        );

        $this->assertFalse($validator->passes());
    }

    /**
     * Test that private IP ranges are blocked.
     */
    public function test_blocks_private_ip_ranges(): void
    {
        $privateIps = [
            'http://10.0.0.1',
            'http://172.16.0.1',
            'http://192.168.1.1',
            'http://169.254.1.1',
        ];

        foreach ($privateIps as $ip) {
            $rule = new SafeUrl;
            $validator = Validator::make(
                ['url' => $ip],
                ['url' => ['required', $rule]]
            );

            $this->assertFalse($validator->passes(), "Failed to block private IP: {$ip}");
        }
    }

    /**
     * Test that blocked hostnames are rejected.
     */
    public function test_blocks_blocked_hostnames(): void
    {
        $blockedHosts = [
            'http://metadata.google.internal',
            'http://metadata.azure.com',
            'http://169.254.169.254',
        ];

        foreach ($blockedHosts as $host) {
            $rule = new SafeUrl;
            $validator = Validator::make(
                ['url' => $host],
                ['url' => ['required', $rule]]
            );

            $this->assertFalse($validator->passes(), "Failed to block hostname: {$host}");
        }
    }

    /**
     * Test that non-http/https schemes are blocked.
     */
    public function test_blocks_non_http_schemes(): void
    {
        $invalidSchemes = [
            'file:///etc/passwd',
            'ftp://example.com',
            'javascript:alert(1)',
        ];

        foreach ($invalidSchemes as $url) {
            $rule = new SafeUrl;
            $validator = Validator::make(
                ['url' => $url],
                ['url' => ['required', $rule]]
            );

            $this->assertFalse($validator->passes(), "Failed to block invalid scheme: {$url}");
        }
    }

    /**
     * Test that invalid URLs are rejected.
     */
    public function test_blocks_invalid_urls(): void
    {
        $invalidUrls = [
            'not-a-url',
            'http://',
            'https://',
            '',
        ];

        foreach ($invalidUrls as $url) {
            $rule = new SafeUrl;
            $validator = Validator::make(
                ['url' => $url],
                ['url' => ['required', $rule]]
            );

            $this->assertFalse($validator->passes(), "Failed to block invalid URL: {$url}");
        }
    }

    /**
     * Test that valid public domains pass.
     */
    public function test_allows_valid_public_domains(): void
    {
        $validUrls = [
            'https://example.com',
            'http://test.example.com',
            'https://subdomain.example.co.uk',
            'example.com',
            'www.example.com',
        ];

        foreach ($validUrls as $url) {
            $rule = new SafeUrl;
            $validator = Validator::make(
                ['url' => $url],
                ['url' => ['required', $rule]]
            );

            $this->assertTrue($validator->passes(), "Failed to allow valid URL: {$url}");
        }
    }
}
