<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;

class JwtTokenService
{
    public function issue(User $user): array
    {
        $now = time();
        $tokenId = (string) Str::uuid();
        $accessToken = $this->encode($user, $tokenId, 'access', $now, $now + config('jwt.access_seconds'));
        $refreshToken = $this->encode($user, $tokenId, 'refresh', $now, $now + config('jwt.refresh_seconds'));

        DB::table('auth_tokens')->insert([
            'id' => $tokenId,
            'user_id' => $user->getKey(),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'expires_at' => now()->addSeconds(config('jwt.refresh_seconds')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['access' => $accessToken, 'refresh' => $refreshToken];
    }

    public function userFromRequest(Request $request): ?User
    {
        $claims = $this->decode($request->cookie(config('jwt.access_cookie')), 'access');
        if (! $claims) {
            return null;
        }

        $token = DB::table('auth_tokens')
            ->where('id', $claims->jti)
            ->where('user_id', $claims->sub)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $token) {
            return null;
        }

        return User::with(['group', 'branch.area'])->whereKey($claims->sub)->where('is_active', true)->first();
    }

    public function rotate(?string $refreshToken): ?array
    {
        $claims = $this->decode($refreshToken, 'refresh');
        if (! $claims) {
            return null;
        }

        return DB::transaction(function () use ($claims, $refreshToken): ?array {
            $token = DB::table('auth_tokens')->where('id', $claims->jti)->lockForUpdate()->first();
            if (! $token || $token->revoked_at || Carbon::parse($token->expires_at)->isPast()
                || (string) $token->user_id !== $claims->sub
                || ! hash_equals($token->refresh_token_hash, hash('sha256', $refreshToken))) {
                return null;
            }

            $user = User::with(['group', 'branch.area'])->whereKey($claims->sub)->where('is_active', true)->first();
            if (! $user) {
                return null;
            }

            DB::table('auth_tokens')->where('id', $claims->jti)->update(['revoked_at' => now(), 'updated_at' => now()]);

            return ['user' => $user, 'tokens' => $this->issue($user)];
        });
    }

    public function revoke(Request $request): void
    {
        $claims = $this->decode($request->cookie(config('jwt.refresh_cookie')), 'refresh')
            ?? $this->decode($request->cookie(config('jwt.access_cookie')), 'access');

        if ($claims) {
            DB::table('auth_tokens')
                ->where('id', $claims->jti)
                ->where('user_id', $claims->sub)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'updated_at' => now()]);
        }
    }

    private function encode(User $user, string $tokenId, string $type, int $issuedAt, int $expiresAt): string
    {
        return JWT::encode([
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
            'sub' => (string) $user->getKey(),
            'jti' => $tokenId,
            'typ' => $type,
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $expiresAt,
        ], $this->key(), 'HS256');
    }

    private function decode(?string $token, string $type): ?stdClass
    {
        if (! $token) {
            return null;
        }

        $key = $this->key();
        try {
            $claims = JWT::decode($token, new Key($key, 'HS256'));
        } catch (\Exception) {
            return null;
        }

        if (($claims->iss ?? null) !== config('jwt.issuer')
            || ($claims->aud ?? null) !== config('jwt.audience')
            || ($claims->typ ?? null) !== $type
            || ! is_string($claims->sub ?? null)
            || ! ctype_digit($claims->sub)
            || ! is_string($claims->jti ?? null)) {
            return null;
        }

        return $claims;
    }

    private function key(): string
    {
        $encoded = config('jwt.secret');
        $key = is_string($encoded) ? base64_decode($encoded, true) : false;
        if ($key === false || strlen($key) < 32) {
            throw new RuntimeException('JWT_SECRET harus berupa kunci base64 minimal 32 byte.');
        }

        return $key;
    }
}
