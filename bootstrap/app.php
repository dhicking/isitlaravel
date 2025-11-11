<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add response compression to all web requests
        $middleware->web(prepend: [
            \App\Http\Middleware\CompressResponse::class,
        ]);

        // Add cache control headers (before security headers so they can be overridden if needed)
        $middleware->web(append: [
            \App\Http\Middleware\CacheControl::class,
        ]);

        // Add security headers to all web requests
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
