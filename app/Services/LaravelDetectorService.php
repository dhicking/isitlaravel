<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LaravelDetectorService
{
    private const HIGH_CONFIDENCE_THRESHOLD = 3;

    private const LOW_CONFIDENCE_THRESHOLD = 1;

    private const TOTAL_INDICATORS = 15;

    /**
     * Detect if a website is built with Laravel by analyzing various indicators.
     *
     * @param  string  $url  The URL to analyze
     * @return array{success: bool, url: string, indicators?: array<string, bool>, score?: int, totalIndicators?: int, confidence?: array{level: string, emoji: string, message: string, cssClass: string}, laravel404CheckStatus?: string, inertiaComponent?: string|null, livewireCount?: int, detectedTools?: array<string>, percentage?: int, error?: string}
     */
    public function detect(string $url): array
    {
        // Normalize URL
        $url = $this->normalizeUrl($url);

        $indicators = [
            'xsrfToken' => false,
            'laravelSession' => false,
            'csrfMeta' => false,
            'tokenInput' => false,
            'viteClient' => false,
            'inertia' => false,
            'livewire' => false,
            'laravel404' => false,
            'laravelTools' => false,
            'mixManifest' => false,
            'bladeComments' => false,
            'laravelEcho' => false,
            'breezeJetstream' => false,
            'upEndpoint' => false,
            'poweredByHeader' => false,
        ];

        $laravel404CheckStatus = 'not-checked';
        $inertiaComponent = null;
        $livewireCount = 0;
        $detectedTools = [];

        try {
            // Fetch the main page
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Referer' => 'https://www.google.com/',
                ])
                ->get($url);

            if (! $response->successful()) {
                // Provide more helpful error messages for common status codes
                if ($response->status() === 403) {
                    throw new \Exception('Access denied (HTTP 403). This website is blocking automated requests. Try analyzing a different site or check if the URL is correct.');
                }
                if ($response->status() === 404) {
                    throw new \Exception('Website not found (HTTP 404). Please check the URL and try again.');
                }
                throw new \Exception("Failed to fetch URL: HTTP {$response->status()}");
            }

            $html = $response->body();
            $cookies = $response->cookies();
            $headers = $response->headers();

            if (str_contains($html, '{{--')) {
                $indicators['bladeComments'] = true;
            }

            if ($this->detectLaravelEcho($html)) {
                $indicators['laravelEcho'] = true;
            }

            if ($this->detectBreezeJetstream($html)) {
                $indicators['breezeJetstream'] = true;
            }

            // Check cookies
            foreach ($cookies as $cookie) {
                $cookieName = $cookie->getName();
                if (str_contains(strtolower($cookieName), 'xsrf-token')) {
                    $indicators['xsrfToken'] = true;
                }
                if (str_contains(strtolower($cookieName), 'laravel_session')) {
                    $indicators['laravelSession'] = true;
                }
            }

            // Check for X-Powered-By or Server headers
            if (isset($headers['X-Powered-By'])) {
                $poweredBy = is_array($headers['X-Powered-By']) ? implode(' ', $headers['X-Powered-By']) : $headers['X-Powered-By'];
                if (stripos($poweredBy, 'laravel') !== false) {
                    $indicators['poweredByHeader'] = true;
                }
            }

            // Check HTML content for various indicators
            $indicators['csrfMeta'] = $this->containsPattern($html, '<meta\s+name=["\']csrf-token["\']');
            $indicators['tokenInput'] = $this->containsPattern($html, '<input[^>]+name=["\']_token["\']');

            // Check for Vite
            $vitePatterns = [
                'src=["\'][^"\']*@vite',
                'src=["\'][^"\']*@vite/client',
                'src=["\'][^"\']*\/build\/',
                'src=["\'][^"\']*\/build\/assets\/',
                'href=["\'][^"\']*\/build\/assets\/',
            ];
            foreach ($vitePatterns as $pattern) {
                if ($this->containsPattern($html, $pattern)) {
                    $indicators['viteClient'] = true;
                    break;
                }
            }

            // Check for Inertia.js
            if ($this->containsPattern($html, 'data-page')) {
                $indicators['inertia'] = true;
                // Try to extract component name
                if (preg_match('/data-page=["\']([^"\']+)["\']/', $html, $matches)) {
                    $pageData = json_decode(html_entity_decode($matches[1]), true);
                    if (isset($pageData['component'])) {
                        $inertiaComponent = $pageData['component'];
                    }
                }
            }

            // Check for Livewire
            $livewirePatterns = ['wire:id', 'wire:initial-data', 'window\.Livewire', 'Livewire\.'];
            foreach ($livewirePatterns as $pattern) {
                if (str_contains($html, $pattern)) {
                    $indicators['livewire'] = true;
                    // Count Livewire components
                    preg_match_all('/wire:id/', $html, $matches);
                    $livewireCount = count($matches[0]);
                    break;
                }
            }

            // Check Laravel 404 page
            // Run additional checks in parallel for better performance
            $parallelChecks = $this->runParallelChecks($url);

            // Extract results from parallel checks
            $indicators['laravel404'] = $parallelChecks['laravel404']['detected'];
            $laravel404CheckStatus = $parallelChecks['laravel404']['status'];
            $indicators['laravelTools'] = $parallelChecks['laravelTools']['detected'];
            $detectedTools = $parallelChecks['laravelTools']['tools'];
            $indicators['mixManifest'] = $parallelChecks['mixManifest'];
            $indicators['upEndpoint'] = $parallelChecks['upEndpoint'];

        } catch (\Exception $e) {
            Log::error('Laravel detection error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
            ];
        }

        // Calculate score
        $score = array_sum(array_map(fn ($val) => $val ? 1 : 0, $indicators));

        // Determine confidence level
        $confidence = $this->getConfidence($score, $indicators);

        // Calculate percentage based on confidence level and indicators
        $percentage = $this->calculatePercentage($score, $indicators, $confidence['level']);

        return [
            'success' => true,
            'url' => $url,
            'indicators' => $indicators,
            'score' => $score,
            'totalIndicators' => self::TOTAL_INDICATORS,
            'confidence' => $confidence,
            'laravel404CheckStatus' => $laravel404CheckStatus,
            'inertiaComponent' => $inertiaComponent,
            'livewireCount' => $livewireCount,
            'detectedTools' => $detectedTools,
            'percentage' => $percentage,
        ];
    }

    /**
     * Check if the website has a Laravel-styled 404 error page.
     *
     * @param  string  $url  The base URL to check
     * @return array{detected: bool, status: string}
     */
    private function checkLaravel404Page(string $url): array
    {
        try {
            // Generate random path
            $randomPath = '/laravel-detector-check-'.bin2hex(random_bytes(8));
            $testUrl = rtrim($url, '/').$randomPath;

            $response = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($testUrl);

            if ($response->status() === 404) {
                $html = $response->body();

                // Check for Laravel 404 page indicators
                $indicators = [
                    // Title indicators
                    str_contains($html, '<title>404') || str_contains($html, '<title>Not Found'),
                    // Tailwind CSS classes common in Laravel
                    str_contains($html, 'min-h-screen'),
                    str_contains($html, 'bg-gray-100'),
                    str_contains($html, 'text-gray-500'),
                    str_contains($html, 'antialiased'),
                    // Common Laravel error page text
                    str_contains($html, 'PAGE NOT FOUND'),
                    str_contains($html, 'laravel') || str_contains($html, 'Laravel'),
                    str_contains($html, 'Oops!'),
                    str_contains($html, 'We could not find the page'),
                    // Layout classes
                    str_contains($html, 'max-w-xl'),
                    str_contains($html, 'mx-auto'),
                    // SVG with 404
                    (str_contains($html, '<svg') && str_contains($html, '404')),
                ];

                $matchCount = count(array_filter($indicators));
                $detected = $matchCount >= 3; // Need at least 3 indicators

                return [
                    'detected' => $detected,
                    'status' => $detected ? 'found' : 'not-found',
                ];
            }

            return [
                'detected' => false,
                'status' => 'no-404',
            ];
        } catch (\Exception $e) {
            Log::warning('Laravel 404 check failed: '.$e->getMessage());

            return [
                'detected' => false,
                'status' => 'failed',
            ];
        }
    }

    /**
     * Calculate the confidence level based on detection score and indicators.
     *
     * @param  int  $score  The total detection score
     * @param  array<string, bool>  $indicators  The array of detected indicators
     * @return array{level: string, emoji: string, message: string, cssClass: string}
     */
    private function getConfidence(int $score, array $indicators): array
    {
        $cssClass = '';
        $emoji = '';
        $message = '';

        // Laravel tools detection is an extremely strong indicator
        // If any Laravel tools are found, it's almost certainly a Laravel app
        if ($indicators['laravelTools']) {
            $emoji = '🎯';
            $message = 'Highly likely Laravel!';
            $cssClass = 'success';
            $level = 'high';
        }
        // Laravel 404 page is extremely specific to Laravel
        // It's a dead giveaway that this is a Laravel application
        elseif ($indicators['laravel404']) {
            $emoji = '🎯';
            $message = 'Highly likely Laravel!';
            $cssClass = 'success';
            $level = 'high';
        }
        // Inertia and Livewire are also Laravel-specific frameworks
        elseif ($indicators['inertia'] || $indicators['livewire']) {
            $emoji = '🎯';
            $message = 'Highly likely Laravel!';
            $cssClass = 'success';
            $level = 'high';
        }
        // Multiple indicators suggest Laravel
        elseif ($score >= self::HIGH_CONFIDENCE_THRESHOLD) {
            $emoji = '🎯';
            $message = 'Highly likely Laravel!';
            $cssClass = 'success';
            $level = 'high';
        }
        // Some indicators found
        elseif ($score >= self::LOW_CONFIDENCE_THRESHOLD) {
            $emoji = '🤔';
            $message = 'Possibly Laravel';
            $cssClass = 'warning';
            $level = 'medium';
        }
        // No strong indicators
        else {
            $emoji = '❓';
            $message = 'Unlikely to be Laravel';
            $cssClass = 'danger';
            $level = 'low';
        }

        return [
            'level' => $level,
            'emoji' => $emoji,
            'message' => $message,
            'cssClass' => $cssClass,
        ];
    }

    /**
     * Normalize and validate a URL by adding protocol if missing.
     *
     * @param  string  $url  The URL to normalize
     * @return string The normalized URL
     *
     * @throws \InvalidArgumentException If the URL is invalid
     */
    private function normalizeUrl(string $url): string
    {
        // Add protocol if missing
        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://'.$url;
        }

        // Validate URL
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL provided');
        }

        return $url;
    }

    /**
     * Check if HTML content contains a regex pattern.
     *
     * @param  string  $html  The HTML content to search
     * @param  string  $pattern  The regex pattern to search for
     * @return bool True if pattern is found, false otherwise
     */
    private function containsPattern(string $html, string $pattern): bool
    {
        // Use a different delimiter to avoid conflicts with forward slashes in patterns
        return preg_match('~'.$pattern.'~i', $html) === 1;
    }

    /**
     * Check if Laravel development tools are detected at common endpoints.
     *
     * Checks for Telescope, Horizon, Nova, and Pulse by looking for
     * protected routes (401/403 responses) or successful responses.
     *
     * @param  string  $url  The base URL to check
     * @return array{detected: bool, tools: array<string>}
     */
    private function checkLaravelTools(string $url): array
    {
        $tools = ['telescope', 'horizon', 'nova', 'pulse'];
        $detected = [];

        foreach ($tools as $tool) {
            try {
                $testUrl = rtrim($url, '/').'/'.$tool;

                $response = Http::timeout(3)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    ])
                    ->get($testUrl);

                // If we get a 403 (Forbidden) or 401 (Unauthorized), it means the route exists but is protected
                // This is a strong indicator that the Laravel tool is installed
                if (in_array($response->status(), [401, 403])) {
                    $detected[] = ucfirst($tool);
                }

                // Also check if we get a 200 and the response contains tool-specific indicators
                if ($response->status() === 200) {
                    $html = $response->body();
                    if (stripos($html, $tool) !== false || stripos($html, 'laravel') !== false) {
                        $detected[] = ucfirst($tool);
                    }
                }
            } catch (\Exception $e) {
                // Silently continue to next tool
                continue;
            }
        }

        return [
            'detected' => count($detected) > 0,
            'tools' => $detected,
        ];
    }

    /**
     * Check if the website supports index.php routing.
     *
     * Laravel applications typically support accessing URLs with index.php
     * in the path, even when it's normally removed via .htaccess.
     *
     * @param  string  $url  The base URL to check
     * @return bool True if index.php routing works, false otherwise
     */
    private function checkIndexPhp(string $url): bool
    {
        try {
            $parsedUrl = parse_url($url);
            $baseUrl = $parsedUrl['scheme'].'://'.$parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $baseUrl .= ':'.$parsedUrl['port'];
            }

            // Add index.php to the path
            $path = $parsedUrl['path'] ?? '/';
            if ($path === '/') {
                $testUrl = $baseUrl.'/index.php';
            } else {
                $testUrl = $baseUrl.'/index.php'.$path;
            }

            $response = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get($testUrl);

            // If we get a successful response, Laravel's routing is likely handling it
            // Laravel removes index.php by default but still supports it
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the /up health check endpoint exists.
     *
     * Laravel 11+ includes a default /up endpoint that returns
     * a simple 200 response with minimal or empty content.
     *
     * @param  string  $url  The base URL to check
     * @return bool True if /up endpoint exists, false otherwise
     */
    private function checkUpEndpoint(string $url): bool
    {
        try {
            $testUrl = rtrim($url, '/').'/up';

            $response = Http::timeout(3)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get($testUrl);

            // Laravel 11+ returns a simple response with status 200
            // The response body is typically empty or contains minimal content
            if ($response->successful()) {
                $body = trim($response->body());
                // Laravel's /up endpoint returns an empty response or very minimal content
                // If the body is empty or very short, it's likely Laravel's health check
                if (strlen($body) < 100) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Calculate a confidence percentage that makes sense with the detection level.
     *
     * @param  int  $score  The raw detection score
     * @param  array<string, bool>  $indicators  The array of detected indicators
     * @param  string  $confidenceLevel  The confidence level (high, medium, low)
     * @return int The percentage confidence (0-100)
     */
    private function calculatePercentage(int $score, array $indicators, string $confidenceLevel): int
    {
        // If we detected Laravel tools, Laravel 404 page, Inertia, or Livewire, confidence should be very high
        if ($indicators['laravelTools'] || $indicators['laravel404'] || $indicators['inertia'] || $indicators['livewire']) {
            // Base high confidence, adjusted by total indicators found
            return min(95, 85 + ($score * 2));
        }

        // High confidence - should be in the 75-95% range
        if ($confidenceLevel === 'high') {
            return min(95, 70 + ($score * 5));
        }

        // Medium confidence - should be in the 35-65% range
        if ($confidenceLevel === 'medium') {
            return min(65, 30 + ($score * 10));
        }

        // Low confidence - use the raw calculation
        return round(($score / self::TOTAL_INDICATORS) * 100);
    }

    /**
     * Run multiple independent checks in parallel for better performance.
     *
     * @param  string  $url  The base URL to check
     * @return array{laravel404: array{detected: bool, status: string}, laravelTools: array{detected: bool, tools: array<string>}, mixManifest: bool, upEndpoint: bool}
     */
    private function runParallelChecks(string $url): array
    {
        // Prepare URLs for parallel requests
        $randomPath = '/laravel-detector-check-'.bin2hex(random_bytes(8));
        $testUrl404 = rtrim($url, '/').$randomPath;
        $testUrlUp = rtrim($url, '/').'/up';
        $mixManifestUrl = rtrim($url, '/').'/mix-manifest.json';

        // Build pool of requests (Note: pool returns numeric indices, not named keys)
        [$response404, $responseUp, $responseMixManifest, $responseTelescope, $responseHorizon, $responseNova, $responsePulse] = Http::pool(function ($pool) use ($testUrl404, $testUrlUp, $mixManifestUrl, $url) {
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ];

            return [
                // 404 check
                $pool->timeout(5)->withHeaders(array_merge($headers, ['Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8']))->get($testUrl404),
                // /up endpoint check
                $pool->timeout(3)->withHeaders($headers)->get($testUrlUp),
                // Mix manifest check
                $pool->timeout(3)->withHeaders($headers)->get($mixManifestUrl),
                // Laravel tools checks
                $pool->timeout(3)->withHeaders($headers)->get(rtrim($url, '/').'/telescope'),
                $pool->timeout(3)->withHeaders($headers)->get(rtrim($url, '/').'/horizon'),
                $pool->timeout(3)->withHeaders($headers)->get(rtrim($url, '/').'/nova'),
                $pool->timeout(3)->withHeaders($headers)->get(rtrim($url, '/').'/pulse'),
            ];
        });

        // Process 404 check result
        $laravel404 = ['detected' => false, 'status' => 'not-checked'];
        try {
            if ($response404->status() === 404) {
                $html = $response404->body();
                $indicators = [
                    str_contains($html, '<title>404') || str_contains($html, '<title>Not Found'),
                    str_contains($html, 'min-h-screen'),
                    str_contains($html, 'bg-gray-100'),
                    str_contains($html, 'text-gray-500'),
                    str_contains($html, 'antialiased'),
                    str_contains($html, 'PAGE NOT FOUND'),
                    str_contains($html, 'laravel') || str_contains($html, 'Laravel'),
                    str_contains($html, 'Oops!'),
                    str_contains($html, 'We could not find the page'),
                    str_contains($html, 'max-w-xl'),
                    str_contains($html, 'mx-auto'),
                    (str_contains($html, '<svg') && str_contains($html, '404')),
                ];
                $matchCount = count(array_filter($indicators));
                $laravel404 = [
                    'detected' => $matchCount >= 3,
                    'status' => $matchCount >= 3 ? 'found' : 'not-found',
                ];
            } else {
                $laravel404['status'] = 'no-404';
            }
        } catch (\Exception $e) {
            $laravel404['status'] = 'failed';
        }

        // Process Laravel tools results
        $detectedTools = [];
        $toolResponses = [$responseTelescope, $responseHorizon, $responseNova, $responsePulse];
        $toolNames = ['Telescope', 'Horizon', 'Nova', 'Pulse'];

        foreach ($toolResponses as $index => $toolResponse) {
            try {
                if (in_array($toolResponse->status(), [401, 403])) {
                    $detectedTools[] = $toolNames[$index];
                }
            } catch (\Exception $e) {
                // Ignore errors for individual tools
            }
        }

        // Process /up endpoint check
        $upEndpointDetected = false;
        try {
            if ($responseUp->successful()) {
                $body = trim($responseUp->body());
                if (strlen($body) < 100) {
                    $upEndpointDetected = true;
                }
            }
        } catch (\Exception $e) {
            // Ignore error
        }

        $mixManifestDetected = false;
        try {
            if ($responseMixManifest->successful() && $this->isValidMixManifest($responseMixManifest->body())) {
                $mixManifestDetected = true;
            }
        } catch (\Exception $e) {
            // Ignore error
        }

        return [
            'laravel404' => $laravel404,
            'laravelTools' => [
                'detected' => count($detectedTools) > 0,
                'tools' => $detectedTools,
            ],
            'mixManifest' => $mixManifestDetected,
            'upEndpoint' => $upEndpointDetected,
        ];
    }

    /**
     * Determine if the provided content looks like a Laravel Mix manifest.
     */
    private function isValidMixManifest(string $content): bool
    {
        $decoded = json_decode($content, true);

        if (! is_array($decoded) || empty($decoded)) {
            return false;
        }

        $keys = array_keys($decoded);

        return collect($keys)->contains(fn ($key) => str_contains($key, '/js/') || str_contains($key, '/css/'));
    }

    /**
     * Detect Laravel Echo usage from the HTML content.
     */
    private function detectLaravelEcho(string $html): bool
    {
        $lower = strtolower($html);

        return str_contains($lower, 'laravel-echo')
            || str_contains($lower, 'window.echo')
            || str_contains($lower, 'new echo(');
    }

    /**
     * Detect if the layout appears to use the default Breeze / Jetstream styling.
     */
    private function detectBreezeJetstream(string $html): bool
    {
        $lower = strtolower($html);

        $layoutClassesDetected = str_contains($lower, 'font-sans antialiased') && str_contains($lower, 'min-h-screen') && str_contains($lower, 'bg-gray-100');
        $logoComponentDetected = str_contains($lower, 'svg') && str_contains($lower, 'jetstream');

        return $layoutClassesDetected || $logoComponentDetected;
    }
}
