<?php

use App\Http\Controllers\Api\v1\Admin\AdminRewardController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\PasswordResetController;
use App\Http\Controllers\Api\v1\RedeemController;
use App\Http\Controllers\Api\v1\ScanConfirmController;
use App\Http\Controllers\Api\v1\ScanController;
use App\Http\Controllers\Api\v1\VerifyEmailController;
use App\Models\Reward;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->name('api.v1.login');
    Route::post('/register', [AuthController::class, 'register'])->name('api.v1.register');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1')->name('api.v1.password.email');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.logout');
        Route::get('/user', [AuthController::class, 'user'])->name('api.v1.user');
        
        Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend'])->middleware('throttle:6,1')->name('api.v1.verification.send');

        Route::middleware('verified')->group(function (): void {
            Route::post('/scan', ScanController::class)->name('api.v1.scan');
            Route::post('/scan/confirm', ScanConfirmController::class)->name('api.v1.scan.confirm');

            Route::get('/rewards', function () {
                return response()->json([
                    'success' => true,
                    'data'    => Reward::where('stock', '>', 0)->orderByDesc('id')->get(),
                ]);
            })->name('api.v1.rewards.index');

            Route::post('/redeem', RedeemController::class)->name('api.v1.redeem');

            Route::prefix('admin')->middleware('role:admin')->group(function (): void {
                Route::get('/rewards', [AdminRewardController::class, 'index'])->name('api.v1.admin.rewards.index');
                Route::post('/rewards', [AdminRewardController::class, 'store'])->name('api.v1.admin.rewards.store');
                Route::get('/rewards/{reward}', [AdminRewardController::class, 'show'])->name('api.v1.admin.rewards.show');
                Route::match(['put', 'patch'], '/rewards/{reward}', [AdminRewardController::class, 'update'])->name('api.v1.admin.rewards.update');
                Route::delete('/rewards/{reward}', [AdminRewardController::class, 'destroy'])->name('api.v1.admin.rewards.destroy');
            });
        });
    });
});
