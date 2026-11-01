<?php

namespace Tests\Feature\Identity;

use App\Modules\Tenancy\Enums\CompanyRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AccountStatusTest extends TestCase
{
    use DatabaseTransactions;

    public function test_disabled_account_loses_an_existing_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->frontend()
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        // The same in-memory user is used by the test guard, so this change is seen on the next request.
        $user->forceFill(['status' => 'disabled'])->save();

        $this->actingAs($user)->frontend()
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_disabled_account_cannot_access_company_routes(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser(status: 'disabled');
        $this->makeMembership($company, $user, CompanyRole::CompanyAdmin);

        $this->actingAs($user)->frontend()
            ->getJson("/api/v1/companies/{$company->id}")
            ->assertStatus(401);
    }
}
