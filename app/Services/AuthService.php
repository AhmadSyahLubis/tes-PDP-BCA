<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private readonly JwtTokenService $tokens) {}

    public function currentUser(Request $request): User
    {
        return $request->user()->loadMissing(['group', 'branch.area']);
    }

    public function login(array $credentials): array
    {
        $user = User::with(['group', 'branch.area'])->where('username', $credentials['username'])->first();
        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['username' => 'Username atau kata sandi salah, atau akun tidak aktif.']);
        }

        return ['user' => $user, 'tokens' => $this->tokens->issue($user)];
    }

    public function refresh(Request $request): ?array
    {
        return $this->tokens->rotate($request->cookie(config('jwt.refresh_cookie')));
    }

    public function logout(Request $request): void
    {
        $this->tokens->revoke($request);
    }
}
