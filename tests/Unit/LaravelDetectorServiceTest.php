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
                '<html><head><script src="/build/assets/@vite/app-abc123.js"></script></head></html>',
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
                '<html><head><script src="/build/assets/@vite/app.js"></script></head><body><div id="app" data-page="{&quot;component&quot;:&quot;Dashboard&quot;,&quot;props&quot;:{&quot;_token&quot;:&quot;abc123&quot;}}">Test</div></body></html>',
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

    public function test_detects_flux_ui(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><link rel="stylesheet" href="/vendor/flux/app.css"></head><body><div class="flux-button">Test</div><script src="/vendor/flux/app.js"></script></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['flux']);
        // Flux requires Livewire, so Livewire should also be detected
        $this->assertTrue($result['indicators']['livewire']);
    }

    public function test_detects_flux_ui_with_components(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body><x-flux::button>Click</x-flux::button><div id="flux" class="flux-container">Content</div></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['flux']);
        // Flux requires Livewire, so Livewire should also be detected
        $this->assertTrue($result['indicators']['livewire']);
    }

    public function test_does_not_detect_flux_with_single_indicator(): void
    {
        // Flux detection requires at least 2 indicators to reduce false positives
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body><div class="flux-button">Test</div></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['indicators']['flux']);
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

    public function test_detects_laravel_echo_with_explicit_package(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><script src="/js/laravel-echo.js"></script></head><body>Test</body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravelEcho']);
    }

    public function test_does_not_detect_apple_echo_as_laravel_echo(): void
    {
        // Apple's Echo analytics library should not be detected as Laravel Echo
        Http::fake([
            'https://store.apple.com' => Http::response(
                '<html><head><script src="https://store.storeimages.cdn-apple.com/4982/store.apple.com/static-resources/rs-echo-3.29.0-21e6e/dist/echo.min.js"></script></head><body><script>window.ECHO_CONFIG = { config: {} };</script></body></html>',
                200
            ),
            'https://store.apple.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('store.apple.com');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['indicators']['laravelEcho']);
    }

    public function test_does_not_detect_generic_echo_without_broadcasting(): void
    {
        // Generic Echo without Laravel broadcasting patterns should not be detected
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body><script>window.Echo = { version: "1.0" };</script></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['indicators']['laravelEcho']);
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

    public function test_detects_laravel_404_page(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();

            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }

            if ($request->method() === 'GET' && str_contains($url, 'laravel-detector-check-')) {
                $html = <<<'HTML'
                <html class="font-sans antialiased">
                    <body class="min-h-screen bg-gray-100">
                        <h1>Laravel</h1>
                        <p>Sorry, the page you are looking for could not be found.</p>
                        <a href="https://laravel.com/docs">Documentation</a>
                    </body>
                </html>
                HTML;

                return Http::response($html, 404);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravel404']);
    }

    public function test_does_not_flag_generic_404_page_as_laravel(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();

            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }

            if ($request->method() === 'GET' && str_contains($url, 'laravel-detector-check-')) {
                $html = <<<'HTML'
                <html class="font-sans antialiased">
                    <body class="min-h-screen bg-blue-50">
                        <h1>Oops! Page not found</h1>
                        <p>This is a generic 404 page.</p>
                    </body>
                </html>
                HTML;

                return Http::response($html, 404);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['indicators']['laravel404']);
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

    public function test_handles_timeout_error_with_friendly_message(): void
    {
        Http::fake([
            'https://example.com' => function () {
                throw new \Exception('cURL error 28: Operation timed out after 10001 milliseconds with 0 bytes received');
            },
        ]);

        $result = $this->service->detect('example.com');

        $this->assertFalse($result['success']);
        $this->assertEquals(
            'Request timed out. This website may be blocking automated detection.',
            $result['error']
        );
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
        $this->assertEquals('certain', $result['confidence']['level']);
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

    public function test_detects_filament_from_html(): void
    {
        $this->fakeParallelRequests(function ($request) {
            if ($request->url() === 'https://example.com') {
                return Http::response(
                    '<html><head><link rel="stylesheet" href="/vendor/filament/assets/app.css"></head><body><div id="filament">Admin Panel</div></body></html>',
                    200
                );
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['filament']);
    }

    public function test_detects_filament_from_login_page(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();
            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }
            if (str_ends_with($url, '/filament/login')) {
                return Http::response('<html><head><title>Filament Login</title></head><body><div id="filament">Login</div></body></html>', 200);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['filament']);
    }

    public function test_detects_statamic_from_html(): void
    {
        $this->fakeParallelRequests(function ($request) {
            if ($request->url() === 'https://example.com') {
                return Http::response(
                    '<html><head><meta name="generator" content="Statamic"></head><body>Content</body></html>',
                    200
                );
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['statamic']);
    }

    public function test_detects_statamic_from_control_panel(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();
            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }
            if (str_ends_with($url, '/cp')) {
                return Http::response('<html><head><title>Statamic Control Panel</title></head><body>CP</body></html>', 200);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['statamic']);
    }

    public function test_detects_telescope_signature(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();
            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }
            if (str_ends_with($url, '/telescope')) {
                $html = '<html><head><link rel="stylesheet" href="/telescope/app.css"><script src="/vendor/telescope/app.js"></script></head><body><div id="telescope">Telescope</div></body></html>';

                return Http::response($html, 200);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravelTools']);
        $this->assertContains('Telescope', $result['detectedTools']);
    }

    public function test_detects_horizon_signature(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();
            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }
            if (str_ends_with($url, '/horizon')) {
                $html = '<html><head><link rel="stylesheet" href="/horizon/app.css"></head><body><div id="horizon" data-horizon="true">Horizon</div><script>window.horizon = {};</script></body></html>';

                return Http::response($html, 200);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravelTools']);
        $this->assertContains('Horizon', $result['detectedTools']);
    }

    public function test_detects_nova_signature(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();
            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }
            if (str_ends_with($url, '/nova')) {
                $html = '<html><head><link rel="stylesheet" href="/nova/app.css"></head><body><div id="nova" data-nova="true">Nova</div><script>window.nova = {};</script></body></html>';

                return Http::response($html, 200);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravelTools']);
        $this->assertContains('Nova', $result['detectedTools']);
    }

    public function test_detects_pulse_signature(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();
            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }
            if (str_ends_with($url, '/pulse')) {
                $html = '<html><head><link rel="stylesheet" href="/pulse/app.css"></head><body><div id="pulse" data-pulse="true">Pulse</div><script>window.pulse = {};</script></body></html>';

                return Http::response($html, 200);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravelTools']);
        $this->assertContains('Pulse', $result['detectedTools']);
    }

    public function test_detects_laravel_tools_with_401_response(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();
            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }
            if (str_ends_with($url, '/telescope')) {
                // 401 response with Telescope-specific content
                $html = '<html><head><link rel="stylesheet" href="/telescope/app.css"><script src="/vendor/telescope/app.js"></script></head><body><div id="telescope">Unauthorized</div></body></html>';

                return Http::response($html, 401);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravelTools']);
        $this->assertContains('Telescope', $result['detectedTools']);
    }

    public function test_detects_laravel_tools_with_403_response(): void
    {
        $this->fakeParallelRequests(function ($request) {
            $url = $request->url();
            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }
            if (str_ends_with($url, '/horizon')) {
                // 403 response with Horizon-specific content
                $html = '<html><head><link rel="stylesheet" href="/horizon/app.css"><script src="/vendor/horizon/app.js"></script></head><body><div id="horizon">Forbidden</div></body></html>';

                return Http::response($html, 403);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravelTools']);
        $this->assertContains('Horizon', $result['detectedTools']);
    }

    public function test_does_not_detect_tools_with_generic_403_response(): void
    {
        // Test that a generic 403 response (same as random path) doesn't trigger false positives
        $generic403Body = '<html><body><h1>403 Forbidden</h1><p>Access denied</p></body></html>';

        $this->fakeParallelRequests(function ($request) use ($generic403Body) {
            $url = $request->url();
            if ($url === 'https://example.com') {
                return Http::response('<html><body>Test</body></html>', 200);
            }
            // Return generic 403 for both tool path and random path
            if (str_ends_with($url, '/telescope') || str_contains($url, '/random-nonexistent-')) {
                return Http::response($generic403Body, 403);
            }

            return null;
        });

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        // Should not detect tools when 403 is generic (same as random path)
        $this->assertFalse($result['indicators']['laravelTools']);
        $this->assertEmpty($result['detectedTools']);
    }

    public function test_does_not_detect_inertia_without_laravel_indicators(): void
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
        $this->assertFalse($result['indicators']['inertia']);
    }

    public function test_detects_inertia_with_laravel_mix(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><script src="/js/app.js"></script><script>laravel-mix = {}</script></head><body><div id="app" data-page="{&quot;component&quot;:&quot;Dashboard&quot;,&quot;props&quot;:{}}">Test</div></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['inertia']);
    }

    public function test_detects_inertia_with_csrf_header_and_errors(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><meta name="x-csrf-token" content="abc123"><script src="/build/assets/@vite/app.js"></script></head><body><div id="app" data-page="{&quot;component&quot;:&quot;Dashboard&quot;,&quot;props&quot;:{&quot;errors&quot;:{}}}">Test</div></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['inertia']);
    }

    public function test_detects_inertia_with_strong_laravel_indicators(): void
    {
        // Test that Inertia is detected when we have strong Laravel indicators
        // (like XSRF-TOKEN cookie) even without Inertia-specific patterns
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head></head><body><div id="app" data-page="{&quot;component&quot;:&quot;Dashboard&quot;,&quot;props&quot;:{}}">Test</div></body></html>',
                200,
                ['Set-Cookie' => 'XSRF-TOKEN=test123']
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['xsrfToken']);
        $this->assertTrue($result['indicators']['inertia'], 'Inertia should be detected when XSRF-TOKEN cookie is present');
        $this->assertEquals('Dashboard', $result['inertiaComponent']);
    }

    public function test_detects_powered_by_header(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body>Test</body></html>',
                200,
                ['X-Powered-By' => 'Laravel']
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['poweredByHeader']);
        $this->assertEquals('certain', $result['confidence']['level']);
        $this->assertEquals(100, $result['percentage']);
    }

    public function test_detects_powered_by_header_case_insensitive(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body>Test</body></html>',
                200,
                ['X-Powered-By' => 'LARAVEL Framework']
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['poweredByHeader']);
    }

    public function test_does_not_detect_inertia_with_unparseable_data_page(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body><div id="app" data-page="invalid-json">Test</div></body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['indicators']['inertia']);
    }

    public function test_detects_laravel_session_cookie_case_insensitive(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body>Test</body></html>',
                200,
                ['Set-Cookie' => 'LARAVEL_SESSION=test123']
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['laravelSession']);
        $this->assertEquals('certain', $result['confidence']['level']);
        $this->assertEquals(100, $result['percentage']);
    }

    public function test_detects_xsrf_token_cookie_case_variations(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><body>Test</body></html>',
                200,
                ['Set-Cookie' => 'xsrf-token=test123']
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['xsrfToken']);
    }

    public function test_detects_vite_with_different_patterns(): void
    {
        $patterns = [
            '<script src="/build/assets/@vite/app.js"></script>',
            '<link href="/build/assets/@vite/app.css" rel="stylesheet">',
            '<script src="/build/assets/@vite/client"></script>',
        ];

        foreach ($patterns as $pattern) {
            Http::fake([
                'https://example.com' => Http::response(
                    "<html><head>{$pattern}</head><body>Test</body></html>",
                    200
                ),
                'https://example.com/*' => Http::response('', 404),
            ]);

            $result = $this->service->detect('example.com');

            $this->assertTrue($result['success'], "Failed for pattern: {$pattern}");
            $this->assertTrue($result['indicators']['viteClient'], "Failed for pattern: {$pattern}");
        }
    }

    public function test_detects_vite_production_builds(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><script type="module" src="/build/assets/app-abc123.js"></script><link href="/build/assets/app-def456.css" rel="stylesheet"></head><body>Test</body></html>',
                200
            ),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['viteClient']);
    }

    public function test_detects_vite_manifest_file(): void
    {
        $viteManifest = json_encode([
            'resources/js/app.js' => [
                'file' => 'assets/app-abc123.js',
                'src' => 'resources/js/app.js',
                'isEntry' => true,
            ],
            'resources/css/app.css' => [
                'file' => 'assets/app-def456.css',
                'src' => 'resources/css/app.css',
            ],
        ]);

        Http::fake([
            'https://example.com' => Http::response(
                '<html><head></head><body>Test</body></html>',
                200
            ),
            'https://example.com/build/.vite/manifest.json' => Http::response($viteManifest, 200),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['viteClient']);
    }

    public function test_detects_vite_manifest_alternative_location(): void
    {
        $viteManifest = json_encode([
            'resources/js/app.js' => [
                'file' => 'assets/app-abc123.js',
                'src' => 'resources/js/app.js',
                'isEntry' => true,
            ],
        ]);

        Http::fake([
            'https://example.com' => Http::response(
                '<html><head></head><body>Test</body></html>',
                200
            ),
            'https://example.com/build/.vite/manifest.json' => Http::response('', 404),
            'https://example.com/.vite/manifest.json' => Http::response($viteManifest, 200),
            'https://example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->detect('example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['indicators']['viteClient']);
    }
}
