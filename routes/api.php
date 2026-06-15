<?php

use App\Http\Controllers\Api\v1\RedeemController;
use App\Http\Controllers\Api\v1\ScanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('/scan', ScanController::class)->name('api.v1.scan');
    Route::post('/redeem', RedeemController::class)->name('api.v1.redeem');
});
