<?php

namespace App\Providers;

use App\Auth\JwtRequestGuard;
use App\Models\CreditApplication;
use App\Models\User;
use App\Policies\CreditApplicationPolicy;
use App\Services\JwtTokenService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Auth::extend('jwt', function ($app) {
            $guard = new JwtRequestGuard(
                fn (Request $request) => $app->make(JwtTokenService::class)->userFromRequest($request),
                $app['request'],
                Auth::createUserProvider('users'),
            );
            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(20)->by('login-ip:'.$request->ip()),
            Limit::perMinute(5)->by('login-user:'.hash('sha256', mb_strtolower((string) $request->input('username'))).'|'.$request->ip()),
        ]);
        RateLimiter::for('refresh', fn (Request $request) => Limit::perMinute(30)->by('refresh-ip:'.$request->ip()));

        Gate::policy(CreditApplication::class, CreditApplicationPolicy::class);
        Gate::define('viewReports', fn (User $user) => $user->hasRole('approver', 'backoffice'));
    }
}
