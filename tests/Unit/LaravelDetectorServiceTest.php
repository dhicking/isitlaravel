<?php

namespace Tests\Unit;

use App\Services\LaravelDetectorService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LaravelDetectorServiceTest extends TestCase
{
    private LaravelDetectorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LaravelDetectorService;
    }

    public function test_detects_laravel_with_xsrf_token_cookie(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body>Test</body></html>',
                200,
                ['Set-Cookie' => 'XSRF-TOKEN=test123']
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['xsrfToken']);
        $this->assertGreaterThan(0, $result['score']);
    }

    public function test_detects_laravel_with_session_cookie(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body>Test</body></html>',
                200,
                ['Set-Cookie' => 'laravel_session=test123']
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravelSession']);
    }

    public function test_detects_csrf_meta_tag(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><meta name="csrf-token" content="abc123"></head><body>Test</body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['csrfMeta']);
    }

    public function test_detects_csrf_token_input(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body><form><input type="hidden" name="_token" value="abc123"></form></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['tokenInput']);
    }

    public function test_detects_vite_build_assets(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><script src="/build/assets/app-abc123.js"></script></head></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['viteClient']);
    }

    public function test_detects_inertia_js(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body><div id="app" data-page="{&quot;component&quot;:&quot;Dashboard&quot;,&quot;props&quot;:{}}">Test</div></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['inertia']);
        $this->assertEquals('Dashboard', $result['inertiaComponent']);
    }

    public function test_detects_livewire(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body><div wire:id="abc123">Test</div></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['livewire']);
        $this->assertEquals(1, $result['livewireCount']);
    }

    public function test_detects_up_endpoint(): void
    {
        Http::fake([
            'https://example.com' => Http::response('<html><body>Test</body></html>', 200),
            'https://example.com/up' => Http::response('', 200),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['upEndpoint']);
    }

    public function test_normalizes_url_without_protocol(): void
    {
        Http::fake([
            'https://example.com' => Http::response('<html><body>Test</body></html>', 200),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertEquals('https://example.com', $result['url']);
    }

    public function test_handles_invalid_url(): void
    {
        $result = $this->service->detect('not-a-valid-url');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_handles_connection_error(): void
    {
        Http::fake([
            'https://example.com' => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        $result = $this->service->detect('example.com');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_calculates_high_confidence_with_multiple_indicators(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><meta name="csrf-token" content="abc"><script src="/build/assets/app.js"></script></head><body><div wire:id="123">Test</div></body></html>',
                200,
                ['Set-Cookie' => 'XSRF-TOKEN=test; laravel_session=test']
            ),
            'https://example.com/up' => Http::response('', 200),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertEquals('high', $result['confidence']['level']);
        $this->assertGreaterThanOrEqual(3, $result['score']);
    }

    public function test_returns_percentage_score(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><meta name="csrf-token" content="abc"></head></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('percentage', $result);
        $this->assertIsNumeric($result['percentage']);
        $this->assertGreaterThanOrEqual(0, $result['percentage']);
        $this->assertLessThanOrEqual(100, $result['percentage']);
    }
}
