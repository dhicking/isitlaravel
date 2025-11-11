<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy
        // Allow Tailwind CDN, Google Fonts, and same-origin resources
        $csp = "default-src 'self'; ".
            "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; ".
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com; ".
            "font-src 'self' https://fonts.gstatic.com data:; ".
            "img-src 'self' data: https:; ".
            "connect-src 'self' https:; ".
            "frame-ancestors 'none';";

        $response->headers->set('Content-Security-Policy', $csp);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // XSS Protection (legacy but still useful)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature Policy)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // HSTS (only if served over HTTPS)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
