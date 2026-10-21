<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function session(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->auth->currentUser($request)]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->validated());

        return $this->withCookies(response()->json(['user' => $result['user']]), $result['tokens'], $request);
    }

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->auth->refresh($request);
        if (! $result) {
            return response()->json(['message' => 'Sesi berakhir. Silakan masuk kembali.'], 401);
        }

        return $this->withCookies(response()->json(['user' => $result['user']]), $result['tokens'], $request);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request);

        return response()->json(['message' => 'Sesi berakhir.'])
            ->withCookie(Cookie::forget(config('jwt.access_cookie')))
            ->withCookie(Cookie::forget(config('jwt.refresh_cookie')));
    }

    private function withCookies(JsonResponse $response, array $tokens, Request $request): JsonResponse
    {
        $secure = $request->isSecure() || (bool) config('session.secure');

        return $response
            ->withCookie(Cookie::make(config('jwt.access_cookie'), $tokens['access'], config('jwt.access_seconds') / 60, '/', null, $secure, true, false, 'lax'))
            ->withCookie(Cookie::make(config('jwt.refresh_cookie'), $tokens['refresh'], config('jwt.refresh_seconds') / 60, '/', null, $secure, true, false, 'lax'));
    }
}
