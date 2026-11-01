<?php

namespace App\Providers;

use App\Modules\Tenancy\Context\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One tenant context per request, so it can never leak into the next request.
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email', ''));
            $key = $email.'|'.$request->ip();

            return Limit::perMinutes(15, 5)->by($key)->response(
                fn (): JsonResponse => response()->json([
                    'error' => [
                        'code' => 'RATE_LIMITED',
                        'message' => 'Too many attempts. Try again later.',
                    ],
                ], 429),
            );
        });
    }
}
