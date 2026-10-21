<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
    }

    public function test_login_refresh_and_logout_rotate_and_revoke_tokens(): void
    {
        $this->seed();
        $login = $this->postJson('/api/login', ['username' => 'marketing', 'password' => 'Demo12345!']);
        $login->assertOk()->assertJsonPath('user.username', 'marketing');
        $access = $this->cookieValue($login, config('jwt.access_cookie'));
        $refresh = $this->cookieValue($login, config('jwt.refresh_cookie'));
        $this->assertNotEmpty($access);
        $this->assertNotEmpty($refresh);
        $this->assertTrue($login->headers->getCookies()[0]->isHttpOnly());

        $this->withUnencryptedCookie(config('jwt.access_cookie'), $access)
            ->getJson('/api/session')->assertOk()->assertJsonPath('user.username', 'marketing');

        $rotated = $this->withUnencryptedCookie(config('jwt.refresh_cookie'), $refresh)->postJson('/api/refresh');
        $rotated->assertOk();
        $newAccess = $this->cookieValue($rotated, config('jwt.access_cookie'));
        $newRefresh = $this->cookieValue($rotated, config('jwt.refresh_cookie'));
        $this->assertNotSame($access, $newAccess);
        $this->assertNotSame($refresh, $newRefresh);

        $this->withUnencryptedCookie(config('jwt.access_cookie'), $access)->getJson('/api/session')->assertUnauthorized();
        $this->withUnencryptedCookie(config('jwt.refresh_cookie'), $refresh)->postJson('/api/refresh')->assertUnauthorized();
        $this->withUnencryptedCookie(config('jwt.access_cookie'), $newAccess)
            ->withUnencryptedCookie(config('jwt.refresh_cookie'), $newRefresh)
            ->postJson('/api/logout')->assertOk();
        $this->withUnencryptedCookie(config('jwt.access_cookie'), $newAccess)->getJson('/api/session')->assertUnauthorized();
    }

    public function test_invalid_token_and_deactivated_user_cannot_enter(): void
    {
        $this->seed();
        $this->withCookie(config('jwt.access_cookie'), 'invalid.jwt.token')
            ->getJson('/api/session')->assertUnauthorized();

        $login = $this->postJson('/api/login', ['username' => 'marketing', 'password' => 'Demo12345!']);
        $access = $this->cookieValue($login, config('jwt.access_cookie'));
        User::where('username', 'marketing')->update(['is_active' => false]);
        $this->withUnencryptedCookie(config('jwt.access_cookie'), $access)->getJson('/api/session')->assertUnauthorized();
    }

    private function cookieValue(TestResponse $response, string $name): string
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie->getValue();
            }
        }

        $this->fail("Cookie {$name} tidak ditemukan.");
    }
}
