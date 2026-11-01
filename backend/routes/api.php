<?php

use App\Http\Controllers\HealthController;
use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Tenancy\Http\Controllers\CompanyController;
use App\Modules\Tenancy\Http\Controllers\PlatformCompanyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');

    Route::middleware('throttle:login')
        ->post('/auth/login', [AuthController::class, 'login'])
        ->name('auth.login');

    Route::middleware('auth')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    });

    Route::middleware(['auth', 'platform.admin'])
        ->prefix('platform')
        ->name('platform.')
        ->group(function (): void {
            Route::get('/companies', [PlatformCompanyController::class, 'index'])->name('companies.index');
            Route::post('/companies', [PlatformCompanyController::class, 'store'])->name('companies.store');
            Route::patch('/companies/{company}/status', [PlatformCompanyController::class, 'updateStatus'])
                ->name('companies.status');
        });

    Route::middleware(['auth', 'company'])
        ->prefix('companies/{company}')
        ->name('companies.')
        ->group(function (): void {
            Route::get('/', [CompanyController::class, 'show'])->name('show');
            Route::patch('/', [CompanyController::class, 'update'])->name('update');
        });
});
