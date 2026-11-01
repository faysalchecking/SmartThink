<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use App\Modules\Tenancy\Context\TenantDatabaseContext;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Models\Company;
use App\Modules\Tenancy\Models\Invitation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RowLevelSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private function context(): TenantDatabaseContext
    {
        return app(TenantDatabaseContext::class);
    }

    private function invitationFor(Company $company, User $actor): Invitation
    {
        $this->context()->apply($company->id);

        return Invitation::query()->create([
            'company_id' => $company->id,
            'email' => 'rls-'.Str::lower(Str::random(10)).'@example.test',
            'role' => CompanyRole::Employee,
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDay(),
            'created_by' => $actor->id,
        ]);
    }

    /** @return list<string> */
    private function visibleInvitationIds(): array
    {
        $ids = DB::table('invitations')->pluck('id')->map(fn ($id) => (string) $id)->all();
        sort($ids);

        return $ids;
    }

    public function test_ti016_company_context_switches_on_one_connection(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $actor = $this->makeUser();

        $invA = $this->invitationFor($companyA, $actor);
        $invB = $this->invitationFor($companyB, $actor);

        $this->context()->apply($companyA->id);
        $this->assertSame([(string) $invA->id], $this->visibleInvitationIds());

        $this->context()->apply($companyB->id);
        $this->assertSame([(string) $invB->id], $this->visibleInvitationIds());

        $this->context()->apply($companyA->id);
        $this->assertSame([(string) $invA->id], $this->visibleInvitationIds());
    }

    public function test_ti017_without_company_context_no_invitation_is_visible(): void
    {
        $company = $this->makeCompany();
        $this->invitationFor($company, $this->makeUser());

        $this->context()->clear();

        $this->assertSame(0, DB::table('invitations')->count());
    }

    public function test_ti017_insert_without_company_context_is_refused(): void
    {
        $company = $this->makeCompany();
        $actor = $this->makeUser();
        $this->context()->clear();

        $this->expectException(QueryException::class);

        // Savepoint, so the outer test transaction stays usable after the refusal.
        DB::transaction(fn () => Invitation::query()->create([
            'company_id' => $company->id,
            'email' => 'nocontext@example.test',
            'role' => CompanyRole::Employee,
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDay(),
            'created_by' => $actor->id,
        ]));
    }

    public function test_ti016_insert_for_another_company_is_refused(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $actor = $this->makeUser();
        $this->context()->apply($companyA->id);

        $this->expectException(QueryException::class);

        DB::transaction(fn () => Invitation::query()->create([
            'company_id' => $companyB->id,
            'email' => 'crosscompany@example.test',
            'role' => CompanyRole::Employee,
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDay(),
            'created_by' => $actor->id,
        ]));
    }

    public function test_ti017_row_level_security_is_enabled_on_invitations(): void
    {
        $enabled = DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'invitations'");

        $this->assertTrue((bool) $enabled->relrowsecurity);
    }

    public function test_ti017_runtime_role_cannot_disable_row_level_security(): void
    {
        $this->expectException(QueryException::class);

        DB::transaction(fn () => DB::statement('ALTER TABLE invitations DISABLE ROW LEVEL SECURITY'));
    }

    public function test_ti010_company_id_in_request_body_is_ignored(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $admin = $this->makeUser();
        $this->makeMembership($companyA, $admin, CompanyRole::CompanyAdmin);
        $email = 'bodyid-'.Str::lower(Str::random(8)).'@example.test';

        $this->actingAs($admin)->frontend()
            ->postJson("/api/v1/companies/{$companyA->id}/invitations", [
                'email' => $email,
                'role' => 'employee',
                'company_id' => $companyB->id,
            ])
            ->assertCreated();

        // RLS hides rows unless a company is set, so check each company explicitly.
        $this->context()->apply($companyA->id);
        $this->assertDatabaseHas('invitations', ['email' => $email, 'company_id' => $companyA->id]);

        $this->context()->apply($companyB->id);
        $this->assertDatabaseMissing('invitations', ['email' => $email]);

        $this->context()->clear();
    }
}
