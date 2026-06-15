<?php

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AuthController;
use App\Models\PointLedger;
use App\Models\Reward;
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
});

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return view('dashboard');
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
    });
});
