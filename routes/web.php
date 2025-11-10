<?php

use App\Http\Controllers\DetectorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DetectorController::class, 'index'])->name('home');
Route::post('/detect', [DetectorController::class, 'detect'])->name('detect');
