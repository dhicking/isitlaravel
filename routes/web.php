<?php

use App\Http\Controllers\DetectorController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DetectorController::class, 'index'])
    ->middleware('throttle:60,1') // 60 requests per minute for homepage
    ->name('home');
Route::post('/detect', [DetectorController::class, 'detect'])
    ->middleware('throttle:15,1') // 15 requests per minute for detection (more restrictive)
    ->name('detect');
Route::get('/results', [DetectorController::class, 'results'])
    ->middleware('throttle:60,1') // 60 requests per minute for results page
    ->name('results');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->middleware('throttle:10,1') // 10 requests per minute for sitemap
    ->name('sitemap');
