<?php

use App\Http\Controllers\Api\v1\Admin\AdminRewardController;
use App\Http\Controllers\Api\v1\RedeemController;
use App\Http\Controllers\Api\v1\ScanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('/scan', ScanController::class)->name('api.v1.scan');
    Route::post('/redeem', RedeemController::class)->name('api.v1.redeem');

    Route::prefix('admin')->middleware('role:admin')->group(function (): void {
        Route::post('/rewards', [AdminRewardController::class, 'store'])->name('api.v1.admin.rewards.store');
    });
});
