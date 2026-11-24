<?php

use App\Modules\Identity\Http\Middleware\EnsureAccountActive;
use App\Modules\Tenancy\Http\Middleware\EnsurePlatformAdmin;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use App\Support\Http\ApiErrorRenderer;
use App\Support\Http\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA mode: session cookie plus CSRF for first-party frontend origins.
        $middleware->statefulApi();

        // Applied to every response, including errors.
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'active' => EnsureAccountActive::class,
            'company' => ResolveCompany::class,
            'platform.admin' => EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every API error has one shape. Internal details are never returned.
        $exceptions->render(function (Throwable $e, Request $request) {
            return $request->is('api/*') ? ApiErrorRenderer::render($e) : null;
        });
    })
    ->create();
