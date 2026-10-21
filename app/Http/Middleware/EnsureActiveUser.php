<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->fresh()?->is_active) {
            return response()->json(['message' => 'Akun tidak aktif.'], 403);
        }

        return $next($request);
    }
}
