<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($request->user()?->role !== $role) {
            // Audit: percobaan akses ke area terlindungi tanpa hak (BFLA).
            Log::channel('security')->warning('authz.denied', [
                'user_id'        => $request->user()?->id,
                'required_role'  => $role,
                'actual_role'    => $request->user()?->role,
                'path'           => $request->path(),
                'method'         => $request->method(),
                'ip'             => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Anda tidak memiliki hak akses untuk fungsi ini.',
                ], 403);
            }

            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
