<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LaravelDetectorService
{
    private const HIGH_CONFIDENCE_THRESHOLD = 3;

    private const LOW_CONFIDENCE_THRESHOLD = 1;

    private const TOTAL_INDICATORS = 17;

    /**
     * Cache TTL in minutes for detection results.
     */
    private const CACHE_TTL_MINUTES = 15;

    /**
     * Detect if a website is built with Laravel by analyzing various indicators.
     *
     * @param  string  $url  The URL to analyze
     * @param  bool  $forceRefresh  Whether to bypass cache and force a fresh detection
     * @return array{success: bool, url: string, indicators?: array<string, bool>, score?: int, totalIndicators?: int, confidence?: array{level: string, emoji: string, message: string, cssClass: string}, laravel404CheckStatus?: string, inertiaComponent?: string|null, livewireCount?: int, detectedTools?: array<string>, percentage?: int, error?: string, cached?: bool}
     */
    public function detect(string $url, bool $forceRefresh = false): array
    {
        // Normalize URL
        $url = $this->normalizeUrl($url);

        // Generate cache key based on normalized URL
        $cacheKey = 'laravel_detection:'.md5($url);
        $cache = Cache::store($this->cacheStore());

        // Return cached result if available and not forcing refresh
        if (! $forceRefresh) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                $cached['cached'] = true;

                return $cached;
            }
        }

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
            'filament' => false,
            'statamic' => false,
            'upEndpoint' => false,
            'poweredByHeader' => false,
        ];

        $laravel404CheckStatus = 'not-checked';
        $inertiaComponent = null;
        $livewireCount = 0;
        $detectedTools = [];
        $faviconUrl = null;
        $filamentFromHtml = false;
        $statamicFromHtml = false;

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

            // Extract favicon URL
            $faviconUrl = $this->extractFaviconUrl($html, $url);

            if (str_contains($html, '{{--')) {
                $indicators['bladeComments'] = true;
            }

            if ($this->detectLaravelEcho($html)) {
                $indicators['laravelEcho'] = true;
            }

            if ($this->detectBreezeJetstream($html)) {
                $indicators['breezeJetstream'] = true;
            }

            $filamentFromHtml = $this->detectFilamentFromHtml($html);
            $statamicFromHtml = $this->detectStatamicFromHtml($html);

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
                // Try to extract component name and check for Laravel-specific patterns
                if (preg_match('/data-page=["\']([^"\']+)["\']/', $html, $matches)) {
                    $pageData = json_decode(html_entity_decode($matches[1]), true);
                    if (isset($pageData['component'])) {
                        $inertiaComponent = $pageData['component'];
                    }

                    // Check if Inertia data contains Laravel-specific indicators
                    $hasLaravelInertia = $this->detectLaravelInertia($pageData, $html);
                    $indicators['inertia'] = $hasLaravelInertia;
                } else {
                    // If we can't parse data-page, just mark as detected (less reliable)
                    $indicators['inertia'] = true;
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
            $indicators['filament'] = $filamentFromHtml || $parallelChecks['filament'];
            $indicators['statamic'] = $statamicFromHtml || $parallelChecks['statamic'];
            $indicators['upEndpoint'] = $parallelChecks['upEndpoint'];

        } catch (\Exception $e) {
            $this->logException('error', 'Laravel detection error', $url, $e);

            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, 'cURL error 28')) {
                $errorMessage = 'Request timed out. This website may be blocking automated detection.';
            }

            $errorResult = [
                'success' => false,
                'error' => $errorMessage,
                'url' => $url,
                'faviconUrl' => null,
                'cached' => false,
            ];

            // Cache errors for a shorter period (5 minutes) to avoid hammering failing sites
            Cache::store($this->cacheStore())->put($cacheKey, $errorResult, now()->addMinutes(5));

            return $errorResult;
        }

        // Calculate score
        $score = array_sum(array_map(fn ($val) => $val ? 1 : 0, $indicators));

        // Determine confidence level
        $confidence = $this->getConfidence($score, $indicators);

        // Calculate percentage based on confidence level and indicators
        $percentage = $this->calculatePercentage($score, $indicators, $confidence['level']);

        $result = [
            'success' => true,
            'url' => $url,
            'faviconUrl' => $faviconUrl,
            'indicators' => $indicators,
            'score' => $score,
            'totalIndicators' => self::TOTAL_INDICATORS,
            'confidence' => $confidence,
            'laravel404CheckStatus' => $laravel404CheckStatus,
            'inertiaComponent' => $inertiaComponent,
            'livewireCount' => $livewireCount,
            'detectedTools' => $detectedTools,
            'percentage' => $percentage,
            'cached' => false,
        ];

        // Cache the result
        $cache->put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $result;
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
            $this->logException('warning', 'Laravel 404 check failed', $url, $e);

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

        $certaintyIndicators = [
            $indicators['laravelTools'],
            $indicators['filament'],
            $indicators['statamic'],
            $indicators['livewire'],
            $indicators['laravelEcho'],
            $indicators['breezeJetstream'],
        ];

        // If we detect any ecosystem packages or tooling that only exist inside Laravel,
        // we can state with certainty that the site is running Laravel.
        if (in_array(true, $certaintyIndicators, true)) {
            $emoji = '✅';
            $message = 'Definitely Laravel';
            $cssClass = 'success';
            $level = 'certain';
        }
        // Laravel 404 page is a strong indicator
        // Inertia.js alone is not definitive (works with Rails, Django, etc.)
        // but combined with other indicators it's very likely Laravel
        elseif ($indicators['laravel404']) {
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
     * Log exceptions with sanitized URL and message.
     */
    private function logException(string $level, string $context, string $url, \Throwable $exception): void
    {
        $host = $this->sanitizeUrlForLog($url);
        $message = $this->sanitizeLogMessage($exception->getMessage());

        Log::log($level, "{$context} for {$host}: {$message}");
    }

    /**
     * Remove URLs from a log message.
     */
    private function sanitizeLogMessage(string $message): string
    {
        $sanitized = preg_replace('/https?:\/\/[^\s]+/i', '[url]', $message);

        if (is_string($sanitized) && $sanitized !== '') {
            return $sanitized;
        }

        return '[redacted]';
    }

    /**
     * Extract host for log context.
     */
    private function sanitizeUrlForLog(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host ?: '[url]';
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
        $certaintyIndicators = [
            $indicators['laravelTools'],
            $indicators['filament'],
            $indicators['statamic'],
            $indicators['livewire'],
            $indicators['laravelEcho'],
            $indicators['breezeJetstream'],
        ];

        if (in_array(true, $certaintyIndicators, true)) {
            return 100;
        }

        // If we detected Laravel 404 page, confidence should be very high
        if ($indicators['laravel404']) {
            return min(95, 85 + ($score * 2));
        }

        // Inertia.js alone is not definitive (framework-agnostic)
        // but it's a strong indicator when combined with other Laravel signs
        if ($indicators['inertia'] && $score >= 2) {
            return min(90, 75 + ($score * 3));
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
     * @return array{
     *     laravel404: array{detected: bool, status: string},
     *     laravelTools: array{detected: bool, tools: array<string>},
     *     mixManifest: bool,
     *     filament: bool,
     *     statamic: bool,
     *     upEndpoint: bool
     * }
     */
    private function runParallelChecks(string $url): array
    {
        // Prepare URLs for parallel requests
        $randomPath = '/laravel-detector-check-'.bin2hex(random_bytes(8));
        $testUrl404 = rtrim($url, '/').$randomPath;
        $testUrlUp = rtrim($url, '/').'/up';
        $mixManifestUrl = rtrim($url, '/').'/mix-manifest.json';
        $filamentLoginUrl = rtrim($url, '/').'/filament/login';
        $adminLoginUrl = rtrim($url, '/').'/admin/login';
        $statamicCpUrl = rtrim($url, '/').'/cp';

        // Build pool of requests (Note: pool returns numeric indices, not named keys)
        [
            $response404,
            $responseUp,
            $responseMixManifest,
            $responseTelescope,
            $responseHorizon,
            $responseNova,
            $responsePulse,
            $responseFilamentLogin,
            $responseAdminLogin,
            $responseStatamicCp,
        ] = Http::pool(function ($pool) use ($testUrl404, $testUrlUp, $mixManifestUrl, $filamentLoginUrl, $adminLoginUrl, $statamicCpUrl, $url) {
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
                // Filament checks
                $pool->timeout(3)->withHeaders($headers)->get($filamentLoginUrl),
                $pool->timeout(3)->withHeaders($headers)->get($adminLoginUrl),
                // Statamic check
                $pool->timeout(3)->withHeaders($headers)->get($statamicCpUrl),
            ];
        });

        // Process 404 check result
        $laravel404 = ['detected' => false, 'status' => 'not-checked'];
        try {
            if ($response404 instanceof Response && $response404->status() === 404) {
                $html = $response404->body();
                $lowerHtml = strtolower($html);

                $containsLaravelWord = str_contains($lowerHtml, 'laravel');
                $containsDefaultMessage = str_contains($lowerHtml, 'page you are looking for could not be found')
                    || str_contains($lowerHtml, 'page you requested could not be found');
                $containsDocsLink = str_contains($lowerHtml, 'laravel.com/docs');
                $containsTailwindLayout = str_contains($lowerHtml, 'font-sans antialiased')
                    && str_contains($lowerHtml, 'min-h-screen')
                    && str_contains($lowerHtml, 'bg-gray-100');

                $laravelSpecificMatches = collect([
                    $containsDefaultMessage,
                    $containsDocsLink,
                    $containsTailwindLayout,
                ])->filter()->count();

                $isLaravel404 = $containsLaravelWord && $laravelSpecificMatches >= 1;

                $laravel404 = [
                    'detected' => $isLaravel404,
                    'status' => $isLaravel404 ? 'found' : 'not-found',
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
        $toolPaths = ['telescope', 'horizon', 'nova', 'pulse'];

        foreach ($toolResponses as $index => $toolResponse) {
            if ($toolResponse instanceof Response) {
                $status = $toolResponse->status();
                $toolName = $toolNames[$index];
                $toolPath = $toolPaths[$index];

                // If we get a 403 (Forbidden) or 401 (Unauthorized), it means the route exists but is protected
                // This is a strong indicator that the Laravel tool is installed
                if (in_array($status, [401, 403])) {
                    $detectedTools[] = $toolName;

                    continue;
                }

                // For 200 responses, check for tool-specific signatures instead of generic keywords
                if ($status === 200) {
                    $html = $toolResponse->body();
                    $lowerHtml = strtolower($html);

                    // Check for tool-specific signatures
                    $isDetected = match ($toolPath) {
                        'telescope' => $this->detectTelescopeSignature($html, $lowerHtml),
                        'horizon' => $this->detectHorizonSignature($html, $lowerHtml),
                        'nova' => $this->detectNovaSignature($html, $lowerHtml),
                        'pulse' => $this->detectPulseSignature($html, $lowerHtml),
                        default => false,
                    };

                    if ($isDetected) {
                        $detectedTools[] = $toolName;
                    }
                }
            }
        }

        // Process /up endpoint check
        $upEndpointDetected = false;
        try {
            if ($responseUp instanceof Response && $responseUp->successful()) {
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
            if ($responseMixManifest instanceof Response && $responseMixManifest->successful() && $this->isValidMixManifest($responseMixManifest->body())) {
                $mixManifestDetected = true;
            }
        } catch (\Exception $e) {
            // Ignore error
        }

        $filamentDetected = $this->detectFilamentFromResponses([$responseFilamentLogin, $responseAdminLogin]);
        $statamicDetected = $this->detectStatamicFromResponses([$responseStatamicCp]);

        return [
            'laravel404' => $laravel404,
            'laravelTools' => [
                'detected' => count($detectedTools) > 0,
                'tools' => $detectedTools,
            ],
            'mixManifest' => $mixManifestDetected,
            'filament' => $filamentDetected,
            'statamic' => $statamicDetected,
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

    /**
     * Extract the favicon URL from HTML or fall back to default.
     *
     * @param  string  $html  The HTML content
     * @param  string  $baseUrl  The base URL of the site
     * @return string|null The favicon URL or null if not found
     */
    private function extractFaviconUrl(string $html, string $baseUrl): ?string
    {
        // Look for favicon in link tags
        $patterns = [
            '/<link[^>]+rel=["\'](?:icon|shortcut icon)["\'][^>]+href=["\']([^"\']+)["\']/i',
            '/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\'](?:icon|shortcut icon)["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $faviconPath = $matches[1];
                // Convert relative URLs to absolute
                if (str_starts_with($faviconPath, 'http://') || str_starts_with($faviconPath, 'https://')) {
                    return $faviconPath;
                }
                if (str_starts_with($faviconPath, '//')) {
                    return 'https:'.$faviconPath;
                }
                if (str_starts_with($faviconPath, '/')) {
                    $parsed = parse_url($baseUrl);

                    return ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '').$faviconPath;
                }
                // Relative path
                $parsed = parse_url($baseUrl);
                $base = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '').($parsed['path'] ?? '/');

                return rtrim($base, '/').'/'.ltrim($faviconPath, '/');
            }
        }

        // Fall back to default favicon.ico location
        $parsed = parse_url($baseUrl);

        return ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '').'/favicon.ico';
    }

    /**
     * Detect Filament from HTML content.
     *
     * @param  string  $html  The HTML content
     * @return bool True if Filament is detected
     */
    private function detectFilamentFromHtml(string $html): bool
    {
        $lower = strtolower($html);

        // Check for Filament asset URLs
        $hasFilamentAssets = str_contains($lower, '/vendor/filament/')
            || str_contains($lower, '/filament/assets/')
            || preg_match('/filament[\/\-]assets/i', $html);

        // Check for Filament-specific HTML identifiers
        $hasFilamentId = str_contains($lower, 'id="filament"')
            || str_contains($lower, "id='filament'");

        // Check for Filament CSS classes
        $hasFilamentClasses = preg_match('/class=["\'][^"\']*filament-/i', $html)
            || str_contains($lower, 'filament-');

        // Check for Livewire components with Filament prefix
        $hasFilamentLivewire = str_contains($lower, '<livewire:filament')
            || str_contains($lower, 'wire:id="filament');

        return $hasFilamentAssets || $hasFilamentId || $hasFilamentClasses || $hasFilamentLivewire;
    }

    /**
     * Detect Statamic from HTML content.
     *
     * @param  string  $html  The HTML content
     * @return bool True if Statamic is detected
     */
    private function detectStatamicFromHtml(string $html): bool
    {
        $lower = strtolower($html);

        // Check for Statamic generator meta tag
        $hasGeneratorTag = str_contains($lower, '<meta name="generator"')
            && str_contains($lower, 'statamic');

        // Check for Statamic asset paths
        $hasStatamicAssets = str_contains($lower, '/vendor/statamic/')
            || str_contains($lower, 'statamic/')
            || preg_match('/statamic[\/\-]assets/i', $html);

        return $hasGeneratorTag || $hasStatamicAssets;
    }

    /**
     * Detect Filament from HTTP responses (login pages).
     *
     * @param  array<int, \Illuminate\Http\Client\Response|\Illuminate\Http\Client\ConnectionException>  $responses  Array of HTTP responses
     * @return bool True if Filament is detected
     */
    private function detectFilamentFromResponses(array $responses): bool
    {
        foreach ($responses as $response) {
            if (! ($response instanceof \Illuminate\Http\Client\Response)) {
                continue;
            }

            try {
                // Check if response is successful (200) and contains Filament indicators
                if ($response->successful()) {
                    $body = strtolower($response->body());

                    // Filament login pages typically contain these indicators
                    $hasFilamentTitle = str_contains($body, 'filament')
                        && (str_contains($body, 'login') || str_contains($body, 'admin'));

                    $hasFilamentAssets = str_contains($body, '/vendor/filament/')
                        || str_contains($body, '/filament/assets/');

                    $hasFilamentId = str_contains($body, 'id="filament"')
                        || str_contains($body, "id='filament'");

                    if ($hasFilamentTitle || $hasFilamentAssets || $hasFilamentId) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors for individual responses
                continue;
            }
        }

        return false;
    }

    /**
     * Detect Statamic from HTTP responses (control panel).
     *
     * @param  array<int, \Illuminate\Http\Client\Response|\Illuminate\Http\Client\ConnectionException>  $responses  Array of HTTP responses
     * @return bool True if Statamic is detected
     */
    private function detectStatamicFromResponses(array $responses): bool
    {
        foreach ($responses as $response) {
            if (! ($response instanceof \Illuminate\Http\Client\Response)) {
                continue;
            }

            try {
                // Check if response is successful (200) and contains Statamic indicators
                if ($response->successful()) {
                    $body = strtolower($response->body());

                    // Statamic control panel typically contains these indicators
                    $hasStatamicTitle = str_contains($body, 'statamic')
                        && (str_contains($body, 'control panel') || str_contains($body, 'cp'));

                    $hasStatamicMeta = str_contains($body, '<meta name="generator"')
                        && str_contains($body, 'statamic');

                    $hasStatamicAssets = str_contains($body, '/vendor/statamic/')
                        || str_contains($body, 'statamic/');

                    if ($hasStatamicTitle || $hasStatamicMeta || $hasStatamicAssets) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors for individual responses
                continue;
            }
        }

        return false;
    }

    /**
     * Detect Laravel Telescope by looking for specific signatures.
     */
    private function detectTelescopeSignature(string $html, string $lowerHtml): bool
    {
        // Telescope-specific asset paths
        $hasTelescopeAssets = str_contains($lowerHtml, '/telescope/app.js')
            || str_contains($lowerHtml, '/telescope/app.css')
            || str_contains($lowerHtml, '/vendor/telescope/')
            || preg_match('/telescope[\/\-]assets/i', $html);

        // Telescope-specific HTML structure
        $hasTelescopeStructure = str_contains($lowerHtml, 'id="telescope"')
            || str_contains($lowerHtml, 'data-telescope')
            || str_contains($lowerHtml, 'telescope-app');

        // Telescope-specific JavaScript
        $hasTelescopeJs = str_contains($lowerHtml, 'window.telescope')
            || str_contains($lowerHtml, 'laravel.telescope');

        // Require at least 2 indicators to reduce false positives
        $indicators = [$hasTelescopeAssets, $hasTelescopeStructure, $hasTelescopeJs];
        $matchCount = count(array_filter($indicators));

        return $matchCount >= 2;
    }

    /**
     * Detect Laravel Horizon by looking for specific signatures.
     */
    private function detectHorizonSignature(string $html, string $lowerHtml): bool
    {
        // Horizon-specific asset paths
        $hasHorizonAssets = str_contains($lowerHtml, '/horizon/app.js')
            || str_contains($lowerHtml, '/horizon/app.css')
            || str_contains($lowerHtml, '/vendor/horizon/')
            || preg_match('/horizon[\/\-]assets/i', $html);

        // Horizon-specific HTML structure
        $hasHorizonStructure = str_contains($lowerHtml, 'id="horizon"')
            || str_contains($lowerHtml, 'data-horizon')
            || str_contains($lowerHtml, 'horizon-app');

        // Horizon-specific JavaScript
        $hasHorizonJs = str_contains($lowerHtml, 'window.horizon')
            || str_contains($lowerHtml, 'laravel.horizon');

        // Require at least 2 indicators to reduce false positives
        $indicators = [$hasHorizonAssets, $hasHorizonStructure, $hasHorizonJs];
        $matchCount = count(array_filter($indicators));

        return $matchCount >= 2;
    }

    /**
     * Detect Laravel Nova by looking for specific signatures.
     */
    private function detectNovaSignature(string $html, string $lowerHtml): bool
    {
        // Nova-specific asset paths
        $hasNovaAssets = str_contains($lowerHtml, '/nova/app.js')
            || str_contains($lowerHtml, '/nova/app.css')
            || str_contains($lowerHtml, '/vendor/nova/')
            || preg_match('/nova[\/\-]assets/i', $html);

        // Nova-specific HTML structure
        $hasNovaStructure = str_contains($lowerHtml, 'id="nova"')
            || str_contains($lowerHtml, 'data-nova')
            || str_contains($lowerHtml, 'nova-app')
            || str_contains($lowerHtml, 'nova-dashboard');

        // Nova-specific JavaScript
        $hasNovaJs = str_contains($lowerHtml, 'window.nova')
            || str_contains($lowerHtml, 'laravel.nova');

        // Require at least 2 indicators to reduce false positives
        $indicators = [$hasNovaAssets, $hasNovaStructure, $hasNovaJs];
        $matchCount = count(array_filter($indicators));

        return $matchCount >= 2;
    }

    /**
     * Detect Laravel Pulse by looking for specific signatures.
     */
    private function detectPulseSignature(string $html, string $lowerHtml): bool
    {
        // Pulse-specific asset paths
        $hasPulseAssets = str_contains($lowerHtml, '/pulse/app.js')
            || str_contains($lowerHtml, '/pulse/app.css')
            || str_contains($lowerHtml, '/vendor/pulse/')
            || preg_match('/pulse[\/\-]assets/i', $html);

        // Pulse-specific HTML structure (Livewire-based)
        $hasPulseStructure = str_contains($lowerHtml, 'id="pulse"')
            || str_contains($lowerHtml, 'data-pulse')
            || str_contains($lowerHtml, 'pulse-dashboard')
            || (str_contains($lowerHtml, 'livewire:pulse') && str_contains($lowerHtml, 'pulse'));

        // Pulse-specific JavaScript
        $hasPulseJs = str_contains($lowerHtml, 'window.pulse')
            || str_contains($lowerHtml, 'laravel.pulse');

        // Require at least 2 indicators to reduce false positives
        $indicators = [$hasPulseAssets, $hasPulseStructure, $hasPulseJs];
        $matchCount = count(array_filter($indicators));

        return $matchCount >= 2;
    }

    /**
     * Detect if Inertia.js is being used with Laravel by checking for Laravel-specific patterns.
     *
     * Inertia.js is framework-agnostic (works with Laravel, Rails, Django, etc.),
     * so we need to look for Laravel-specific indicators in the Inertia setup.
     */
    private function detectLaravelInertia(?array $pageData, string $html): bool
    {
        // If we can't parse the data-page, we can't be sure it's Laravel
        if (! is_array($pageData)) {
            return false;
        }

        $lowerHtml = strtolower($html);

        // Check for Laravel-specific patterns in Inertia setup
        // 1. Laravel CSRF token handling in Inertia
        $hasLaravelCsrf = str_contains($lowerHtml, 'x-csrf-token')
            || str_contains($lowerHtml, 'x-inertia')
            || (isset($pageData['props']) && is_array($pageData['props']) && isset($pageData['props']['_token']));

        // 2. Laravel asset paths combined with Inertia
        $hasLaravelAssets = str_contains($lowerHtml, '/build/')
            || str_contains($lowerHtml, '@vite')
            || str_contains($lowerHtml, 'laravel-mix');

        // 3. Laravel-specific error handling in Inertia responses
        $hasLaravelErrors = isset($pageData['props']['errors'])
            || isset($pageData['props']['flash']);

        // 4. Check if Inertia is combined with other Laravel indicators we've already detected
        // (This will be checked at the confidence level, not here)

        // Require at least one Laravel-specific indicator
        // If none are found, Inertia could be from Rails, Django, etc.
        return $hasLaravelCsrf || $hasLaravelAssets || $hasLaravelErrors;
    }

    /**
     * Determine which cache store should be used for detector results.
     */
    private function cacheStore(): string
    {
        $default = config('cache.default', 'file');

        if ($default === 'database') {
            return 'file';
        }

        return $default;
    }
}
