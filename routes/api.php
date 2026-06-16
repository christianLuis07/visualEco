<?php

use App\Http\Controllers\Api\v1\Admin\AdminRewardController;
use App\Http\Controllers\Api\v1\RedeemController;
use App\Http\Controllers\Api\v1\ScanConfirmController;
use App\Http\Controllers\Api\v1\ScanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('/scan', ScanController::class)->name('api.v1.scan');
    Route::post('/scan/confirm', ScanConfirmController::class)->name('api.v1.scan.confirm');
    Route::post('/redeem', RedeemController::class)->name('api.v1.redeem');

    Route::prefix('admin')->middleware('role:admin')->group(function (): void {
        Route::get('/rewards', [AdminRewardController::class, 'index'])->name('api.v1.admin.rewards.index');
        Route::post('/rewards', [AdminRewardController::class, 'store'])->name('api.v1.admin.rewards.store');
        Route::get('/rewards/{reward}', [AdminRewardController::class, 'show'])->name('api.v1.admin.rewards.show');
        Route::match(['put', 'patch'], '/rewards/{reward}', [AdminRewardController::class, 'update'])->name('api.v1.admin.rewards.update');
        Route::delete('/rewards/{reward}', [AdminRewardController::class, 'destroy'])->name('api.v1.admin.rewards.destroy');
    });
});
