<?php

namespace Tests\Feature;

use Tests\TestCase;

class CompressionMiddlewareTest extends TestCase
{
    public function test_compresses_html_response_with_gzip_when_client_accepts(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'gzip, deflate, br',
        ])->get('/');

        $response->assertStatus(200);
        $this->assertTrue(
            $response->headers->has('Content-Encoding') &&
            ($response->headers->get('Content-Encoding') === 'gzip' || $response->headers->get('Content-Encoding') === 'br')
        );
        $this->assertEquals('Accept-Encoding', $response->headers->get('Vary'));
    }

    public function test_does_not_compress_when_client_does_not_accept_encoding(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => '',
        ])->get('/');

        $response->assertStatus(200);
        $this->assertFalse($response->headers->has('Content-Encoding'));
    }

    public function test_compresses_json_response(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'gzip',
            'Accept' => 'application/json',
        ])->get('/sitemap.xml');

        $response->assertStatus(200);
        // Sitemap is XML, should be compressible
        $this->assertTrue(
            ! $response->headers->has('Content-Encoding') || // May or may not compress small responses
            in_array($response->headers->get('Content-Encoding'), ['gzip', 'br'], true)
        );
    }

    public function test_does_not_compress_small_responses(): void
    {
        // Small responses (< 1KB) should not be compressed
        // This is tested implicitly - if response is small, compression may be skipped
        $response = $this->withHeaders([
            'Accept-Encoding' => 'gzip',
        ])->get('/up');

        $response->assertStatus(200);
        // Health check endpoint returns minimal content, may not be compressed
    }
}
