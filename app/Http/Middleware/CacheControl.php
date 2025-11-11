<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheControl
{
    /**
     * Handle an incoming request and set appropriate Cache-Control headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Don't set cache headers if they're already set
        if ($response->headers->has('Cache-Control')) {
            return $response;
        }

        // Don't cache responses with Set-Cookie or Authorization headers
        // (unless explicitly allowed by Laravel Cloud rules)
        if ($response->headers->has('Set-Cookie') || $response->headers->has('Authorization')) {
            return $response;
        }

        $path = $request->path();
        $contentType = $response->headers->get('Content-Type', '');

        // Static assets - cache for 1 year (these rarely change)
        if ($this->isStaticAsset($path, $contentType)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');

            return $response;
        }

        // HTML pages - cache for 5 minutes (allows for content updates)
        if (str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'public, max-age=300, must-revalidate');

            return $response;
        }

        // JSON responses (API-like) - cache for 1 minute
        if (str_contains($contentType, 'application/json')) {
            $response->headers->set('Cache-Control', 'public, max-age=60, must-revalidate');

            return $response;
        }

        // XML (sitemap) - cache for 1 hour
        if (str_contains($contentType, 'application/xml') || str_contains($contentType, 'text/xml')) {
            $response->headers->set('Cache-Control', 'public, max-age=3600');

            return $response;
        }

        return $response;
    }

    /**
     * Determine if the request is for a static asset.
     */
    private function isStaticAsset(string $path, string $contentType): bool
    {
        // Check by file extension
        $staticExtensions = [
            'ico', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'svgz',
            'woff', 'woff2', 'ttf', 'eot', 'otf',
            'css', 'js',
            'pdf', 'zip', 'gz', 'bz2', 'rar', '7z',
        ];

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, $staticExtensions)) {
            return true;
        }

        // Check by content type
        $staticContentTypes = [
            'image/',
            'font/',
            'application/font',
            'text/css',
            'application/javascript',
            'text/javascript',
        ];

        foreach ($staticContentTypes as $type) {
            if (str_starts_with($contentType, $type)) {
                return true;
            }
        }

        return false;
    }
}
