<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Audit: percobaan login gagal (deteksi brute-force).
            // Email dicatat untuk forensik; password TIDAK PERNAH dicatat.
            Log::channel('security')->warning('auth.login.failed', [
                'email' => $request->input('email'),
                'ip'    => $request->ip(),
                'agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();

        Log::channel('security')->info('auth.login.success', [
            'user_id' => Auth::id(),
            'role'    => Auth::user()->role,
            'ip'      => $request->ip(),
        ]);

        // Arahkan sesuai peran: admin ke panel admin, warga ke dashboard.
        $home = Auth::user()->role === 'admin' ? '/admin' : '/dashboard';

        return redirect()->intended($home);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'user',
        ]);

        event(new \Illuminate\Auth\Events\Registered($user));

        Auth::login($user);

        Log::channel('security')->info('auth.register', [
            'user_id' => $user->id,
            'ip'      => $request->ip(),
        ]);

        return redirect()->route('verification.notice')->with('success', 'Akun berhasil terdaftar! Silakan cek email Anda untuk verifikasi.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::channel('security')->info('auth.logout', [
            'user_id' => $userId,
            'ip'      => $request->ip(),
        ]);

        return redirect('/login');
    }
}
