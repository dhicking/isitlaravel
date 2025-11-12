<?php

use App\Http\Controllers\DetectorController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DetectorController::class, 'index'])
    ->middleware('throttle:60,1') // 60 requests per minute for homepage
    ->name('home');
Route::post('/detect', [DetectorController::class, 'detect'])
    ->middleware('throttle:20,1') // 20 requests per minute for detection (prevents abuse)
    ->name('detect');
Route::get('/results', [DetectorController::class, 'results'])
    ->middleware('throttle:60,1') // 60 requests per minute for results page
    ->name('results');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->middleware('throttle:10,1') // 10 requests per minute for sitemap
    ->name('sitemap');

// Serve favicon to prevent 404 errors
Route::get('/favicon.ico', function () {
    return response()->file(public_path('favicon.ico'), [
        'Content-Type' => 'image/x-icon',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->name('favicon');
