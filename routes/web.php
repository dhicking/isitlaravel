<?php

use App\Http\Controllers\DetectorController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DetectorController::class, 'index'])->name('home');
Route::post('/detect', [DetectorController::class, 'detect'])
    ->middleware('throttle:15,1')
    ->name('detect');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
