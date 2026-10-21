<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CreditApplicationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/api/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/api/refresh', [AuthController::class, 'refresh'])->middleware('throttle:refresh');
Route::post('/api/logout', [AuthController::class, 'logout']);

Route::middleware(['auth:jwt', 'active'])->prefix('api')->group(function () {
    Route::get('/session', [AuthController::class, 'session']);
    Route::get('/applications', [CreditApplicationController::class, 'index']);
    Route::post('/applications', [CreditApplicationController::class, 'store']);
    Route::get('/applications/{application}', [CreditApplicationController::class, 'show']);
    Route::post('/applications/{application}/decision', [CreditApplicationController::class, 'decide']);
    Route::post('/applications/{application}/issue', [CreditApplicationController::class, 'issue']);
    Route::post('/applications/{application}/signed', [CreditApplicationController::class, 'uploadSigned']);
    Route::post('/applications/{application}/disburse', [CreditApplicationController::class, 'disburse']);
    Route::get('/documents/{document}', [CreditApplicationController::class, 'download']);
    Route::get('/reports', [ReportController::class, 'index']);
});

Route::view('/login', 'spa')->name('login');
Route::view('/{path?}', 'spa')->where('path', '^(?!api/).*$');
