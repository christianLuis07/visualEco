<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Reset Password — memanfaatkan password broker bawaan Laravel.
 *
 * Keamanan:
 *  - Anti-enumeration: pesan sukses selalu sama walau email tak terdaftar.
 *  - Token acak (hashed di DB) dengan masa kedaluwarsa (config: 60 menit).
 *  - Throttle pengiriman link (config: 60 detik).
 *  - Password baru di-hash + session token di-regenerate (anti fixation).
 */
class PasswordResetController extends Controller
{
    public function showLinkRequest(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        Log::channel('security')->info('auth.password.reset_requested', [
            'email'  => $request->input('email'),
            'status' => $status,
            'ip'     => $request->ip(),
        ]);

        // Pesan netral (anti-enumeration): tidak membocorkan apakah email terdaftar.
        return back()->with('status',
            'Jika email terdaftar, tautan reset password telah dikirim. Silakan periksa kotak masuk Anda.'
        );
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PasswordReset) {
            Log::channel('security')->info('auth.password.reset_success', [
                'email' => $request->input('email'),
                'ip'    => $request->ip(),
            ]);

            return redirect('/login')->with('status',
                'Password berhasil diubah. Silakan masuk dengan password baru Anda.'
            );
        }

        Log::channel('security')->warning('auth.password.reset_failed', [
            'email'  => $request->input('email'),
            'status' => $status,
            'ip'     => $request->ip(),
        ]);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Token tidak valid atau telah kedaluwarsa.']);
    }
}
