<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Models\Company;
use App\Modules\Tenancy\Models\Invitation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Creates an open invitation directly in the database and returns the raw token.
     */
    private function pendingInvitation(Company $company, User $actor, string $email, CompanyRole $role, ?\DateTimeInterface $expiresAt = null): string
    {
        $raw = Str::random(64);

        Invitation::query()->create([
            'company_id' => $company->id,
            'email' => $email,
            'role' => $role,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => $expiresAt ?? now()->addDay(),
            'created_by' => $actor->id,
        ]);

        return $raw;
    }

    private function newEmail(): string
    {
        return 'invitee-'.Str::lower(Str::random(10)).'@example.test';
    }

    public function test_admin_invitation_stores_only_the_token_hash(): void
    {
        $company = $this->makeCompany();
        $admin = $this->makeUser();
        $this->makeMembership($company, $admin, CompanyRole::CompanyAdmin);
        $email = $this->newEmail();

        $token = $this->actingAs($admin)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/invitations", ['email' => $email, 'role' => 'employee'])
            ->assertCreated()
            ->json('data.invitation_token');

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
        $this->assertDatabaseHas('invitations', ['email' => $email, 'token_hash' => hash('sha256', $token)]);
        $this->assertDatabaseMissing('invitations', ['token_hash' => $token]);
    }

    public function test_employee_cannot_invite(): void
    {
        $company = $this->makeCompany();
        $employee = $this->makeUser();
        $this->makeMembership($company, $employee, CompanyRole::Employee);

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/invitations", ['email' => $this->newEmail(), 'role' => 'employee'])
            ->assertForbidden();
    }

    public function test_invitee_creates_account_and_becomes_an_active_member(): void
    {
        $company = $this->makeCompany();
        $admin = $this->makeUser();
        $this->makeMembership($company, $admin, CompanyRole::CompanyAdmin);
        $email = $this->newEmail();
        $token = $this->pendingInvitation($company, $admin, $email, CompanyRole::Employee);

        $this->frontend()
            ->postJson('/api/v1/invitations/accept', [
                'token' => $token,
                'name' => 'New Member',
                'password' => self::TEST_PASSWORD,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.role', 'employee');

        $newUser = User::query()->where('email', $email)->firstOrFail();

        $this->actingAs($newUser)->frontend()
            ->getJson("/api/v1/companies/{$company->id}")
            ->assertOk();
    }

    public function test_invitation_cannot_be_used_twice(): void
    {
        $company = $this->makeCompany();
        $admin = $this->makeUser();
        $this->makeMembership($company, $admin, CompanyRole::CompanyAdmin);
        $email = $this->newEmail();
        $token = $this->pendingInvitation($company, $admin, $email, CompanyRole::Employee);
        $payload = ['token' => $token, 'name' => 'Once', 'password' => self::TEST_PASSWORD];

        $this->frontend()->postJson('/api/v1/invitations/accept', $payload)->assertCreated();

        $this->frontend()->postJson('/api/v1/invitations/accept', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_expired_invitation_is_rejected(): void
    {
        $company = $this->makeCompany();
        $admin = $this->makeUser();
        $this->makeMembership($company, $admin, CompanyRole::CompanyAdmin);
        $token = $this->pendingInvitation($company, $admin, $this->newEmail(), CompanyRole::Employee, now()->subMinute());

        $this->frontend()
            ->postJson('/api/v1/invitations/accept', ['token' => $token, 'name' => 'Late', 'password' => self::TEST_PASSWORD])
            ->assertStatus(422);
    }

    public function test_signed_in_user_with_a_different_email_cannot_accept(): void
    {
        $company = $this->makeCompany();
        $admin = $this->makeUser();
        $this->makeMembership($company, $admin, CompanyRole::CompanyAdmin);
        $token = $this->pendingInvitation($company, $admin, $this->newEmail(), CompanyRole::Employee);
        $stranger = $this->makeUser();

        $this->actingAs($stranger)->frontend()
            ->postJson('/api/v1/invitations/accept', ['token' => $token])
            ->assertStatus(422);

        $this->assertDatabaseMissing('memberships', ['company_id' => $company->id, 'user_id' => $stranger->id]);
    }

    public function test_admin_cannot_change_a_membership_of_another_company(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $adminA = $this->makeUser();
        $this->makeMembership($companyA, $adminA, CompanyRole::CompanyAdmin);
        $victim = $this->makeUser();
        $victimMembership = $this->makeMembership($companyB, $victim, CompanyRole::Employee);

        $this->actingAs($adminA)->frontend()
            ->patchJson("/api/v1/companies/{$companyA->id}/members/{$victimMembership->id}", ['status' => 'deactivated'])
            ->assertNotFound();

        $this->assertDatabaseHas('memberships', ['id' => $victimMembership->id, 'status' => 'active']);
    }

    public function test_admin_deactivation_is_persisted_and_audited(): void
    {
        $company = $this->makeCompany();
        $admin = $this->makeUser();
        $this->makeMembership($company, $admin, CompanyRole::CompanyAdmin);
        $employee = $this->makeUser();
        $employeeMembership = $this->makeMembership($company, $employee, CompanyRole::Employee);

        $this->actingAs($admin)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/members/{$employeeMembership->id}", ['status' => 'deactivated'])
            ->assertOk()
            ->assertJsonPath('data.status', 'deactivated');

        $this->assertDatabaseHas('memberships', ['id' => $employeeMembership->id, 'status' => 'deactivated']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'membership.updated',
            'target_id' => $employeeMembership->id,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_deactivated_membership_cannot_access_company(): void
    {
        $company = $this->makeCompany();
        $employee = $this->makeUser();
        $this->makeMembership($company, $employee, CompanyRole::Employee, MembershipStatus::Deactivated);

        $this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}")
            ->assertNotFound();
    }

    public function test_last_active_admin_cannot_be_demoted(): void
    {
        $company = $this->makeCompany();
        $admin = $this->makeUser();
        $adminMembership = $this->makeMembership($company, $admin, CompanyRole::CompanyAdmin);

        $this->actingAs($admin)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/members/{$adminMembership->id}", ['role' => 'employee'])
            ->assertStatus(422);

        $this->assertDatabaseHas('memberships', ['id' => $adminMembership->id, 'role' => 'company_admin']);
    }

    public function test_employee_cannot_list_members(): void
    {
        $company = $this->makeCompany();
        $employee = $this->makeUser();
        $this->makeMembership($company, $employee, CompanyRole::Employee);

        $this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}/members")
            ->assertForbidden();
    }

    public function test_admin_sees_only_own_company_members(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $adminA = $this->makeUser();
        $this->makeMembership($companyA, $adminA, CompanyRole::CompanyAdmin);
        $employeeA = $this->makeUser();
        $this->makeMembership($companyA, $employeeA, CompanyRole::Employee);
        $outsider = $this->makeUser();
        $this->makeMembership($companyB, $outsider, CompanyRole::CompanyAdmin);

        $response = $this->actingAs($adminA)->frontend()
            ->getJson("/api/v1/companies/{$companyA->id}/members")
            ->assertOk();

        $this->assertCount(2, $response->json('data'));
        $this->assertStringNotContainsString($outsider->email, $response->getContent());
    }
}
