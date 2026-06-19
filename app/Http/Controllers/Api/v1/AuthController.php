<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            Log::channel('security')->warning('api.login.failed', [
                'email' => $request->input('email'),
                'ip'    => $request->ip(),
                'agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('mobile')->plainTextToken;

        Log::channel('security')->info('api.login.success', [
            'user_id' => $user->id,
            'role'    => $user->role,
            'ip'      => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data'    => [
                'token' => $token,
                'user'  => $this->userPayload($user),
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
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

        $token = $user->createToken('mobile')->plainTextToken;

        Log::channel('security')->info('api.register', [
            'user_id' => $user->id,
            'ip'      => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil. Silakan cek email Anda untuk verifikasi.',
            'data'    => [
                'token' => $token,
                'user'  => $this->userPayload($user),
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $request->user()->currentAccessToken()->delete();

        Log::channel('security')->info('api.logout', [
            'user_id' => $userId,
            'ip'      => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->userPayload($request->user()),
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'role'              => $user->role,
            'points_balance'    => $user->points_balance ?? 0,
        ];
    }
}
