<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    /**
     * Handle an incoming request and compress the response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only compress if client accepts compression and response is compressible
        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        // Get the content
        $content = $response->getContent();

        // Determine compression method (prefer brotli, fallback to gzip)
        $acceptEncoding = $request->header('Accept-Encoding', '');
        $compressed = false;
        $encoding = null;

        // Try Brotli first (better compression)
        // Note: Brotli requires PHP 7.0+ with brotli extension installed
        // Falls back to gzip if not available
        if (function_exists('brotli_compress') && str_contains($acceptEncoding, 'br')) {
            $brotliCompressed = @brotli_compress($content, 6); // Level 6 is a good balance
            if ($brotliCompressed !== false && $brotliCompressed !== '') {
                $compressed = $brotliCompressed;
                $encoding = 'br';
            }
        }

        // Fallback to Gzip if Brotli failed or not supported
        if ($compressed === false && str_contains($acceptEncoding, 'gzip')) {
            $gzipCompressed = gzencode($content, 6); // Level 6 is a good balance
            if ($gzipCompressed !== false) {
                $compressed = $gzipCompressed;
                $encoding = 'gzip';
            }
        }

        // If compression succeeded, update response
        if ($compressed !== false && $encoding !== null) {
            $response->setContent($compressed);
            $response->headers->set('Content-Encoding', $encoding);
            $response->headers->set('Vary', 'Accept-Encoding');
            $response->headers->remove('Content-Length'); // Let server recalculate
        }

        return $response;
    }

    /**
     * Determine if the response should be compressed.
     */
    private function shouldCompress(Request $request, Response $response): bool
    {
        // Don't compress if client doesn't accept encoding
        $acceptEncoding = $request->header('Accept-Encoding', '');
        if (empty($acceptEncoding) || (! str_contains($acceptEncoding, 'gzip') && ! str_contains($acceptEncoding, 'br'))) {
            return false;
        }

        // Don't compress if response is already compressed
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        // Only compress text-based content types
        $contentType = $response->headers->get('Content-Type', '');
        $compressibleTypes = [
            'text/html',
            'text/plain',
            'text/css',
            'text/javascript',
            'application/javascript',
            'application/json',
            'application/xml',
            'application/xhtml+xml',
            'application/xml+rss',
            'application/atom+xml',
            'image/svg+xml',
        ];

        $shouldCompress = false;
        foreach ($compressibleTypes as $type) {
            if (str_contains($contentType, $type)) {
                $shouldCompress = true;
                break;
            }
        }

        // Don't compress very small responses (overhead not worth it)
        if ($shouldCompress && strlen($response->getContent()) < 1024) {
            return false;
        }

        return $shouldCompress;
    }
}
