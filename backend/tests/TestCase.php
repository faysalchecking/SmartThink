<?php

namespace Tests;

use App\Models\User;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Enums\CompanyStatus;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Models\Company;
use App\Modules\Tenancy\Models\Membership;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected const TEST_DATABASE = 'smartthink_test';

    protected const FRONTEND_ORIGIN = 'http://localhost:5173';

    protected const TEST_PASSWORD = 'correct-horse-battery-staple';

    /**
     * Only CsrfTest sets this to true, so it runs the real CSRF check.
     * All other suites disable CSRF, because they test authentication and authorisation.
     */
    protected bool $keepCsrf = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests must never touch the development or production database.
        $database = config('database.connections.pgsql.database');
        if ($database !== self::TEST_DATABASE) {
            throw new RuntimeException('Tests must run against '.self::TEST_DATABASE.' only.');
        }

        if (! $this->keepCsrf) {
            $this->withoutMiddleware(ValidateCsrfToken::class);
        }
    }

    /**
     * Sends the request with the frontend Origin, so Sanctum treats it as a stateful SPA request.
     */
    protected function frontend(): static
    {
        return $this->withHeaders([
            'Origin' => self::FRONTEND_ORIGIN,
            'Accept' => 'application/json',
        ]);
    }

    protected function makeUser(bool $platformAdmin = false, string $status = 'active'): User
    {
        return User::query()->forceCreate([
            'name' => 'Test User',
            'email' => Str::lower((string) Str::uuid()).'@example.test',
            'password' => self::TEST_PASSWORD,
            'is_platform_admin' => $platformAdmin,
            'status' => $status,
        ]);
    }

    protected function makeCompany(CompanyStatus $status = CompanyStatus::Active, string $name = 'Test Company'): Company
    {
        return Company::query()->create([
            'name' => $name,
            'slug' => 'co-'.Str::lower(Str::random(10)),
            'status' => $status,
        ]);
    }

    protected function makeMembership(
        Company $company,
        User $user,
        CompanyRole $role,
        MembershipStatus $status = MembershipStatus::Active,
    ): Membership {
        return Membership::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => $status,
        ]);
    }
}
