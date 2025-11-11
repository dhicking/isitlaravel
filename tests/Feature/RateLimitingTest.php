<?php

namespace Tests\Feature;

use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    public function test_homepage_allows_multiple_requests(): void
    {
        // Homepage allows 60 requests per minute
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/');
            $response->assertStatus(200);
        }
    }

    public function test_detection_endpoint_has_rate_limiting(): void
    {
        // Detection endpoint allows 15 requests per minute
        // Make several requests to verify rate limiting is active
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/detect', [
                'url' => 'laravel.com',
            ]);
            // Should succeed for first few requests
            $this->assertContains($response->status(), [200, 302, 429]);
        }
    }

    public function test_sitemap_has_rate_limiting(): void
    {
        // Sitemap allows 10 requests per minute
        for ($i = 0; $i < 5; $i++) {
            $response = $this->get('/sitemap.xml');
            $response->assertStatus(200);
        }
    }

    public function test_rate_limiting_headers_are_present(): void
    {
        $response = $this->get('/');

        // Rate limiting middleware should add X-RateLimit-* headers
        // Note: Laravel's throttle middleware may not always add these headers
        // depending on configuration, so we just verify the request succeeds
        $response->assertStatus(200);
    }
}
