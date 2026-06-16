<?php

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\PasswordResetController;
use App\Models\PointLedger;
use App\Models\Reward;
use App\Models\WasteCategory;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Guest-only routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Reset Password
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequest'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return view('dashboard', [
            'categories' => WasteCategory::orderBy('id')->get(['id', 'name']),
        ]);
    })->name('dashboard');

    Route::get('/rewards', function () {
        return view('rewards', [
            'rewards' => Reward::where('stock', '>', 0)->orderBy('points_required')->get(),
            'ledgers' => PointLedger::where('user_id', auth()->id())
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    })->name('rewards');

    // Admin routes
    Route::prefix('admin')->middleware('role:admin')->group(function (): void {
        Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::post('/voucher/verify', [AdminController::class, 'verifyVoucher'])->name('admin.voucher.verify');
        Route::patch('/voucher/{id}/complete', [AdminController::class, 'completeRedeem'])->name('admin.voucher.complete');
        Route::post('/model/train', [AdminController::class, 'trainModel'])->name('admin.model.train');
        Route::post('/model/seed-train', [AdminController::class, 'seedTrainModel'])->name('admin.model.seed-train');
    });
});
