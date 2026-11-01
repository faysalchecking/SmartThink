<?php

namespace Tests\Feature\Tenancy;

use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Enums\CompanyStatus;
use App\Modules\Tenancy\Enums\MembershipStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_non_member_gets_404_for_a_company(): void
    {
        $company = $this->makeCompany();
        $outsider = $this->makeUser();

        $this->actingAs($outsider)->frontend()
            ->getJson("/api/v1/companies/{$company->id}")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_malformed_company_id_gets_404(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->frontend()
            ->getJson('/api/v1/companies/not-a-uuid')
            ->assertNotFound();
    }

    public function test_admin_of_company_a_cannot_read_or_update_company_b(): void
    {
        $companyA = $this->makeCompany(name: 'Company A');
        $companyB = $this->makeCompany(name: 'Company B');
        $adminA = $this->makeUser();
        $this->makeMembership($companyA, $adminA, CompanyRole::CompanyAdmin);

        $this->actingAs($adminA)->frontend()
            ->getJson("/api/v1/companies/{$companyB->id}")
            ->assertNotFound();

        $this->actingAs($adminA)->frontend()
            ->patchJson("/api/v1/companies/{$companyB->id}", ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertDatabaseHas('companies', ['id' => $companyB->id, 'name' => 'Company B']);
    }

    public function test_member_can_read_own_company(): void
    {
        $company = $this->makeCompany(name: 'Own Company');
        $employee = $this->makeUser();
        $this->makeMembership($company, $employee, CompanyRole::Employee);

        $this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Own Company');
    }

    public function test_employee_cannot_update_company_settings(): void
    {
        $company = $this->makeCompany();
        $employee = $this->makeUser();
        $this->makeMembership($company, $employee, CompanyRole::Employee);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}", ['name' => 'Renamed'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_admin_can_update_company_and_change_is_audited(): void
    {
        $company = $this->makeCompany(name: 'Old Name');
        $admin = $this->makeUser();
        $this->makeMembership($company, $admin, CompanyRole::CompanyAdmin);

        $this->actingAs($admin)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'New Name']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'company.updated',
            'company_id' => $company->id,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_members_of_suspended_company_get_403(): void
    {
        $company = $this->makeCompany(CompanyStatus::Suspended);
        $admin = $this->makeUser();
        $this->makeMembership($company, $admin, CompanyRole::CompanyAdmin);

        $this->actingAs($admin)->frontend()
            ->getJson("/api/v1/companies/{$company->id}")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_deactivated_membership_gets_404(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser();
        $this->makeMembership($company, $user, CompanyRole::Employee, MembershipStatus::Deactivated);

        $this->actingAs($user)->frontend()
            ->getJson("/api/v1/companies/{$company->id}")
            ->assertNotFound();
    }

    public function test_regular_user_cannot_use_platform_routes(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->frontend()
            ->getJson('/api/v1/platform/companies')
            ->assertForbidden();
    }

    public function test_platform_admin_creates_pending_company(): void
    {
        $platformAdmin = $this->makeUser(platformAdmin: true);
        $slug = 'acme-'.Str::lower(Str::random(6));

        $this->actingAs($platformAdmin)->frontend()
            ->postJson('/api/v1/platform/companies', ['name' => 'Acme Overseas', 'slug' => $slug])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('companies', ['slug' => $slug, 'status' => 'pending']);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $platformAdmin = $this->makeUser(platformAdmin: true);
        $existing = $this->makeCompany();

        $this->actingAs($platformAdmin)->frontend()
            ->postJson('/api/v1/platform/companies', ['name' => 'Copy', 'slug' => $existing->slug])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_pending_company_can_be_activated_but_not_suspended_directly(): void
    {
        $platformAdmin = $this->makeUser(platformAdmin: true);
        $company = $this->makeCompany(CompanyStatus::Pending);

        $this->actingAs($platformAdmin)->frontend()
            ->patchJson("/api/v1/platform/companies/{$company->id}/status", ['status' => 'suspended'])
            ->assertStatus(422);

        $this->actingAs($platformAdmin)->frontend()
            ->patchJson("/api/v1/platform/companies/{$company->id}/status", ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }
}
