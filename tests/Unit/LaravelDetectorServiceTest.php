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

    /**
     * Helper method to create base fakes for parallel requests
     */
    private function fakeParallelRequests(?callable $callback = null): void
    {
        Http::fake(function ($request) use ($callback) {
            $url = $request->url();
            
            // If callback provided, let it handle the request first
            if ($callback) {
                $response = $callback($request);
                if ($response !== null) {
                    return $response;
                }
            }
            
            // Default 404 for everything else
            return Http::response('', 404);
        });
    }

    public function test_detects_laravel_with_xsrf_token_cookie(): void
    {
        $this->fakeParallelRequests(function ($request) {
            if ($request->url() === 'https://example.com') {
                return Http::response(
                    '<html><body>Test</body></html>',
                    200,
                    ['Set-Cookie' => 'XSRF-TOKEN=test123']
                );
            }
            return null;
        });

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
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();
            if (str_ends_with($url, '/up')) {
                return Http::response('', 200);
            }
            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }
            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['upEndpoint']);
    }

    public function test_detects_mix_manifest(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();

            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }

            if (str_ends_with($url, '/mix-manifest.json')) {
                return Http::response(json_encode([
                    '/js/app.js' => '/js/app.js?id=123',
                    '/css/app.css' => '/css/app.css?id=456',
                ]), 200, ['Content-Type' => 'application/json']);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['mixManifest']);
    }

    public function test_detects_blade_comments(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body>{{-- Blade Comment --}}Test</body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['bladeComments']);
    }

    public function test_detects_laravel_echo(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body><script>window.Echo = new Echo({ broadcaster: "pusher" });</script></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravelEcho']);
    }

    public function test_detects_breeze_or_jetstream_layout(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body class="font-sans antialiased"><div class="min-h-screen bg-gray-100">Content</div></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['breezeJetstream']);
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
