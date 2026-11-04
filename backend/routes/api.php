<?php

use App\Http\Controllers\HealthController;
use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Recruitment\Http\Controllers\ClientController;
use App\Modules\Recruitment\Http\Controllers\JobOrderController;
use App\Modules\Tenancy\Http\Controllers\CompanyController;
use App\Modules\Tenancy\Http\Controllers\InvitationController;
use App\Modules\Tenancy\Http\Controllers\MemberController;
use App\Modules\Tenancy\Http\Controllers\PlatformCompanyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');

    Route::middleware('throttle:login')
        ->post('/auth/login', [AuthController::class, 'login'])
        ->name('auth.login');

    Route::middleware('throttle:invitations')
        ->post('/invitations/accept', [InvitationController::class, 'accept'])
        ->name('invitations.accept');

    Route::middleware(['auth', 'active'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::patch('/auth/password', [AuthController::class, 'changePassword'])->name('auth.password');
    });

    Route::middleware(['auth', 'active', 'platform.admin'])
        ->prefix('platform')
        ->name('platform.')
        ->group(function (): void {
            Route::get('/companies', [PlatformCompanyController::class, 'index'])->name('companies.index');
            Route::post('/companies', [PlatformCompanyController::class, 'store'])->name('companies.store');
            Route::patch('/companies/{company}/status', [PlatformCompanyController::class, 'updateStatus'])
                ->name('companies.status');
        });

    Route::middleware(['auth', 'active', 'company'])
        ->prefix('companies/{company}')
        ->name('companies.')
        ->group(function (): void {
            Route::get('/', [CompanyController::class, 'show'])->name('show');
            Route::patch('/', [CompanyController::class, 'update'])->name('update');

            Route::get('/members', [MemberController::class, 'index'])->name('members.index');
            Route::patch('/members/{member}', [MemberController::class, 'update'])->name('members.update');
            Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
            Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
            Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
            Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
            Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');

            Route::get('/jobs', [JobOrderController::class, 'index'])->name('jobs.index');
            Route::post('/jobs', [JobOrderController::class, 'store'])->name('jobs.store');
            Route::get('/jobs/{job}', [JobOrderController::class, 'show'])->name('jobs.show');
            Route::patch('/jobs/{job}', [JobOrderController::class, 'update'])->name('jobs.update');
        });
});
