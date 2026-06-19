<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function sendLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        Log::channel('security')->info('api.password.reset_requested', [
            'email'  => $request->input('email'),
            'status' => $status,
            'ip'     => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jika email terdaftar, tautan reset password telah dikirim. Silakan periksa kotak masuk Anda.',
        ]);
    }
}
