<?php

namespace Tests\Feature\Recruitment;

use App\Models\User;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Recruitment\Models\CandidateApplication;
use App\Modules\Recruitment\Models\Client;
use App\Modules\Recruitment\Models\JobOrder;
use App\Modules\Recruitment\Models\PipelineStage;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Models\Company;
use App\Modules\Tenancy\Models\Membership;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ExportReportTest extends TestCase
{
    use DatabaseTransactions;

    private const CSV_HEADER = 'full_name,email,phone,nationality,date_of_birth';

    private function memberOf(Company $company, CompanyRole $role): User
    {
        $user = $this->makeUser();
        $this->makeMembership($company, $user, $role);

        return $user;
    }

    private function candidateIn(Company $company, User $actor, string $name, ?string $passport = null): string
    {
        return (string) $this->inCompany($company, fn () => Candidate::query()->create([
            'full_name' => $name,
            'email' => 'person-'.bin2hex(random_bytes(4)).'@example.test',
            'passport_number' => $passport,
            'created_by' => $actor->id,
        ])->id);
    }

    private function jobIn(Company $company, User $actor): string
    {
        return (string) $this->inCompany($company, function () use ($actor): string {
            $client = Client::query()->create(['name' => 'Report Client']);

            return (string) JobOrder::query()->create([
                'client_id' => $client->id,
                'title' => 'Driver',
                'status' => 'open',
                'created_by' => $actor->id,
            ])->id;
        });
    }

    private function applyIn(Company $company, User $actor, string $candidateId, string $jobId, string $stageName): void
    {
        $this->inCompany($company, function () use ($actor, $candidateId, $jobId, $stageName): void {
            CandidateApplication::query()->create([
                'candidate_id' => $candidateId,
                'job_order_id' => $jobId,
                'stage_id' => PipelineStage::query()->where('name', $stageName)->value('id'),
                'status' => 'active',
                'created_by' => $actor->id,
            ]);
        });
    }

    /**
     * Returns the CSV body without the byte order mark.
     */
    private function csvOf(object $response): string
    {
        assert($response instanceof TestResponse);

        $content = $response->streamedContent();

        return str_starts_with($content, "\xEF\xBB\xBF") ? substr($content, 3) : $content;
    }

    private function exportAuditCount(): int
    {
        return (int) DB::table('audit_logs')->where('action', 'export.candidates')->count();
    }

    public function test_admin_export_has_fixed_columns_no_passport_and_audits_only_the_row_count(): void
    {
        $company = $this->makeCompany();
        $admin = $this->memberOf($company, CompanyRole::CompanyAdmin);
        $this->candidateIn($company, $admin, 'Rahim Uddin', 'PassportSecret99');

        $response = $this->actingAs($admin)->frontend()
            ->get("/api/v1/companies/{$company->id}/exports/candidates.csv")
            ->assertOk();

        $csv = $this->csvOf($response);
        $this->assertStringContainsString(self::CSV_HEADER, $csv);
        $this->assertStringContainsString('Rahim Uddin', $csv);
        $this->assertStringNotContainsString('PassportSecret99', $csv);

        $metadata = (string) DB::table('audit_logs')->where('action', 'export.candidates')->latest('id')->value('metadata');
        $decoded = json_decode($metadata, true);

        $this->assertIsArray($decoded);
        $this->assertSame(1, $decoded['row_count']);
        $this->assertSame(['row_count'], array_keys($decoded));
        $this->assertStringNotContainsString('Rahim', $metadata);
    }

    public function test_employee_without_permission_cannot_export_and_nothing_is_audited(): void
    {
        $company = $this->makeCompany();
        $employee = $this->memberOf($company, CompanyRole::Employee);

        $this->actingAs($employee)->frontend()
            ->get("/api/v1/companies/{$company->id}/exports/candidates.csv")
            ->assertForbidden();

        $this->assertSame(0, $this->exportAuditCount());
    }

    public function test_admin_grant_and_revoke_are_persisted_and_audited(): void
    {
        $company = $this->makeCompany();
        $admin = $this->memberOf($company, CompanyRole::CompanyAdmin);
        $employee = $this->memberOf($company, CompanyRole::Employee);
        $membershipId = (string) Membership::query()->where('user_id', $employee->id)->value('id');

        $this->actingAs($admin)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/members/{$membershipId}/export-permission", ['can_export' => true])
            ->assertOk();

        $this->assertTrue((bool) Membership::query()->whereKey($membershipId)->value('can_export'));

        $this->actingAs($admin)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/members/{$membershipId}/export-permission", ['can_export' => false])
            ->assertOk();

        $this->assertFalse((bool) Membership::query()->whereKey($membershipId)->value('can_export'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'membership.export_permission_changed', 'target_id' => $membershipId]);
    }

    public function test_employee_with_granted_permission_can_export(): void
    {
        $company = $this->makeCompany();
        $employee = $this->memberOf($company, CompanyRole::Employee);
        Membership::query()->where('user_id', $employee->id)->update(['can_export' => true]);

        $this->actingAs($employee)->frontend()
            ->get("/api/v1/companies/{$company->id}/exports/candidates.csv")
            ->assertOk();
    }

    public function test_employee_cannot_grant_export_permission_to_themselves(): void
    {
        $company = $this->makeCompany();
        $employee = $this->memberOf($company, CompanyRole::Employee);
        $membershipId = (string) Membership::query()->where('user_id', $employee->id)->value('id');

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/members/{$membershipId}/export-permission", ['can_export' => true])
            ->assertForbidden();

        $this->assertFalse((bool) Membership::query()->whereKey($membershipId)->value('can_export'));
    }

    public function test_formula_like_names_are_neutralised_in_the_csv(): void
    {
        $company = $this->makeCompany();
        $admin = $this->memberOf($company, CompanyRole::CompanyAdmin);
        $this->candidateIn($company, $admin, '=HYPERLINK("http://example.test","x")');

        $csv = $this->csvOf($this->actingAs($admin)->frontend()
            ->get("/api/v1/companies/{$company->id}/exports/candidates.csv"));

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_export_excludes_archived_candidates_and_other_companies(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $adminA = $this->memberOf($companyA, CompanyRole::CompanyAdmin);
        $adminB = $this->memberOf($companyB, CompanyRole::CompanyAdmin);

        $this->candidateIn($companyA, $adminA, 'Visible Person');
        $this->candidateIn($companyB, $adminB, 'Other Company Person');
        $archivedId = $this->candidateIn($companyA, $adminA, 'Archived Person');

        $this->inCompany($companyA, function () use ($archivedId): void {
            Candidate::query()->whereKey($archivedId)->firstOrFail()->forceFill(['archived_at' => now()])->save();
        });

        $csv = $this->csvOf($this->actingAs($adminA)->frontend()
            ->get("/api/v1/companies/{$companyA->id}/exports/candidates.csv"));

        $this->assertStringContainsString('Visible Person', $csv);
        $this->assertStringNotContainsString('Archived Person', $csv);
        $this->assertStringNotContainsString('Other Company Person', $csv);
    }

    public function test_export_above_the_row_limit_is_refused_and_not_audited(): void
    {
        config(['smartthink.export_max_rows' => 2]);

        $company = $this->makeCompany();
        $admin = $this->memberOf($company, CompanyRole::CompanyAdmin);
        $this->candidateIn($company, $admin, 'Person One');
        $this->candidateIn($company, $admin, 'Person Two');
        $this->candidateIn($company, $admin, 'Person Three');

        $this->actingAs($admin)->frontend()
            ->get("/api/v1/companies/{$company->id}/exports/candidates.csv")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'EXPORT_TOO_LARGE');

        $this->assertSame(0, $this->exportAuditCount());
    }

    public function test_pipeline_report_counts_active_applications_per_job_and_stage(): void
    {
        $company = $this->makeCompany();
        $admin = $this->memberOf($company, CompanyRole::CompanyAdmin);
        $jobId = $this->jobIn($company, $admin);

        $first = $this->candidateIn($company, $admin, 'Report One');
        $second = $this->candidateIn($company, $admin, 'Report Two');
        $this->applyIn($company, $admin, $first, $jobId, 'Screening');
        $this->applyIn($company, $admin, $second, $jobId, 'Interview');

        // An application in another company must not appear.
        $other = $this->makeCompany(name: 'Other');
        $otherAdmin = $this->memberOf($other, CompanyRole::CompanyAdmin);
        $otherJob = $this->jobIn($other, $otherAdmin);
        $otherCandidate = $this->candidateIn($other, $otherAdmin, 'Other Report');
        $this->applyIn($other, $otherAdmin, $otherCandidate, $otherJob, 'Screening');

        $data = (array) $this->actingAs($admin)->frontend()
            ->getJson("/api/v1/companies/{$company->id}/reports/pipeline")
            ->assertOk()
            ->json('data');

        $totals = [];
        foreach ($data as $row) {
            $totals[(string) $row['stage_name']] = (int) $row['total'];
        }

        $this->assertEqualsCanonicalizing(['Screening' => 1, 'Interview' => 1], $totals);
    }
}
