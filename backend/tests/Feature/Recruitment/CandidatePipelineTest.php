<?php

namespace Tests\Feature\Recruitment;

use App\Models\User;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Recruitment\Models\CandidateApplication;
use App\Modules\Recruitment\Models\Client;
use App\Modules\Recruitment\Models\JobOrder;
use App\Modules\Recruitment\Models\PipelineStage;
use App\Modules\Recruitment\Models\StageHistory;
use App\Modules\Tenancy\Context\TenantDatabaseContext;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Models\Company;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CandidatePipelineTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsFrom(mixed $json): array
    {
        $rows = [];

        if (! is_array($json)) {
            return $rows;
        }

        foreach ($json as $item) {
            if (is_array($item)) {
                $rows[] = $item;
            }
        }

        return $rows;
    }

    private function employeeOf(Company $company): User
    {
        $user = $this->makeUser();
        $this->makeMembership($company, $user, CompanyRole::Employee);

        return $user;
    }

    private function adminOf(Company $company): User
    {
        $user = $this->makeUser();
        $this->makeMembership($company, $user, CompanyRole::CompanyAdmin);

        return $user;
    }

    private function openJob(Company $company, User $actor, string $status = 'open'): string
    {
        return (string) $this->inCompany($company, function () use ($actor, $status): string {
            $client = Client::query()->create(['name' => 'Client '.Str::random(6)]);

            return (string) JobOrder::query()->create([
                'client_id' => $client->id,
                'title' => 'Driver',
                'status' => $status,
                'created_by' => $actor->id,
            ])->id;
        });
    }

    private function stageId(Company $company, string $name): string
    {
        return (string) $this->inCompany($company, fn () => PipelineStage::query()->where('name', $name)->value('id'));
    }

    private function historyCount(Company $company, string $applicationId): int
    {
        return (int) $this->inCompany($company, fn () => StageHistory::query()
            ->where('application_id', $applicationId)
            ->count());
    }

    private function createCandidate(Company $company, User $actor, string $name = 'Candidate One'): string
    {
        return (string) $this->actingAs($actor)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/candidates", ['full_name' => $name])
            ->assertCreated()
            ->json('data.id');
    }

    private function startApplication(Company $company, User $actor, string $candidateId, string $jobId): string
    {
        return (string) $this->actingAs($actor)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/applications", [
                'candidate_id' => $candidateId,
                'job_order_id' => $jobId,
            ])
            ->assertCreated()
            ->json('data.id');
    }

    public function test_default_pipeline_is_seeded_with_overseas_stages(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);

        $response = $this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}/pipeline-stages")
            ->assertOk();

        $rows = $this->rowsFrom($response->json('data'));
        $names = array_column($rows, 'name');
        $this->assertSame(
            ['Screening', 'Interview', 'Selected', 'Passport Check', 'Medical', 'Visa', 'Embassy', 'Deployment'],
            $names,
        );

        $kinds = array_column($rows, 'kind', 'name');
        $this->assertSame('overseas', $kinds['Medical']);
        $this->assertSame('standard', $kinds['Screening']);
    }

    public function test_employee_cannot_create_or_change_pipeline_stages(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $stageId = $this->stageId($company, 'Medical');

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/pipeline-stages", ['name' => 'Extra', 'kind' => 'standard'])
            ->assertForbidden();

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/pipeline-stages/{$stageId}", ['enabled' => false])
            ->assertForbidden();
    }

    public function test_admin_can_add_a_stage_and_disable_an_unused_overseas_stage(): void
    {
        $company = $this->makeCompany();
        $admin = $this->adminOf($company);
        $medicalId = $this->stageId($company, 'Medical');

        $this->actingAs($admin)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/pipeline-stages", ['name' => 'Final Check', 'kind' => 'overseas'])
            ->assertCreated()
            ->assertJsonPath('data.enabled', true);

        $this->actingAs($admin)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/pipeline-stages/{$medicalId}", ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);
    }

    public function test_candidate_passport_is_encrypted_at_rest_and_masked_in_api(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);

        $response = $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/candidates", [
                'full_name' => 'Rahim Uddin',
                'passport_number' => 'AB1234567',
            ])
            ->assertCreated()
            ->assertJsonPath('data.passport_masked', '******567');

        $this->assertStringNotContainsString('AB1234567', $response->getContent());

        $id = (string) $response->json('data.id');

        $raw = $this->inCompany($company, fn () => DB::table('candidates')->where('id', $id)->value('passport_number'));
        $this->assertNotSame('AB1234567', $raw);
        $this->assertStringNotContainsString('AB1234567', (string) $raw);

        $decrypted = $this->inCompany($company, fn () => Candidate::query()->whereKey($id)->firstOrFail()->passport_number);
        $this->assertSame('AB1234567', $decrypted);
    }

    public function test_candidate_of_another_company_is_not_found(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $creatorB = $this->makeUser();

        $secret = $this->inCompany($companyB, fn () => Candidate::query()->create([
            'full_name' => 'Secret Person',
            'created_by' => $creatorB->id,
        ]));

        $this->actingAs($employeeA)->frontend()
            ->getJson("/api/v1/companies/{$companyA->id}/candidates/{$secret->id}")
            ->assertNotFound();

        $this->actingAs($employeeA)->frontend()
            ->patchJson("/api/v1/companies/{$companyA->id}/candidates/{$secret->id}", ['full_name' => 'Hijacked'])
            ->assertNotFound();

        $this->actingAs($employeeA)->frontend()
            ->postJson("/api/v1/companies/{$companyA->id}/candidates/{$secret->id}/archive")
            ->assertNotFound();

        $name = $this->inCompany($companyB, fn () => Candidate::query()->whereKey($secret->id)->value('full_name'));
        $this->assertSame('Secret Person', $name);
    }

    public function test_archived_candidates_are_hidden_unless_requested(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $id = $this->createCandidate($company, $employee, 'Archive Me');

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/candidates/{$id}/archive")
            ->assertOk()
            ->assertJsonPath('data.archived', true);

        $visibleRows = $this->rowsFrom($this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}/candidates")
            ->json('data'));
        $this->assertNotContains($id, array_column($visibleRows, 'id'));

        $allRows = $this->rowsFrom($this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}/candidates?include_archived=1")
            ->json('data'));
        $this->assertContains($id, array_column($allRows, 'id'));

    }

    public function test_search_matches_only_the_own_company(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $creatorB = $this->makeUser();

        $this->createCandidate($companyA, $employeeA, 'Karim Hossain');
        $this->inCompany($companyB, fn () => Candidate::query()->create([
            'full_name' => 'Karim Other',
            'created_by' => $creatorB->id,
        ]));

        $rows = $this->rowsFrom($this->actingAs($employeeA)->frontend()
            ->getJson("/api/v1/companies/{$companyA->id}/candidates?q=karim")
            ->json('data'));
        $names = array_column($rows, 'full_name');

        $this->assertSame(['Karim Hossain'], $names);
    }

    public function test_application_starts_in_the_first_enabled_stage_and_is_recorded(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->createCandidate($company, $employee);
        $jobId = $this->openJob($company, $employee);

        $applicationId = $this->startApplication($company, $employee, $candidateId, $jobId);

        $this->assertSame($this->stageId($company, 'Screening'), $this->inCompany(
            $company,
            fn () => CandidateApplication::query()->whereKey($applicationId)->value('stage_id'),
        ));
        $this->assertSame(1, $this->historyCount($company, $applicationId));
    }

    public function test_application_to_a_closed_job_is_rejected(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->createCandidate($company, $employee);
        $jobId = $this->openJob($company, $employee, 'closed');

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/applications", [
                'candidate_id' => $candidateId,
                'job_order_id' => $jobId,
            ])
            ->assertStatus(422);
    }

    public function test_same_candidate_cannot_apply_twice_to_one_job(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->createCandidate($company, $employee);
        $jobId = $this->openJob($company, $employee);

        $this->startApplication($company, $employee, $candidateId, $jobId);

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/applications", [
                'candidate_id' => $candidateId,
                'job_order_id' => $jobId,
            ])
            ->assertStatus(422);
    }

    public function test_candidate_of_another_company_cannot_be_applied(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $employeeB = $this->employeeOf($companyB);
        $jobA = $this->openJob($companyA, $employeeA);

        // Company B's candidate is created directly, so only one HTTP request is made in this test.
        $candidateB = $this->inCompany($companyB, fn () => Candidate::query()->create([
            'full_name' => 'Other Company Candidate',
            'created_by' => $employeeB->id,
        ]));

        $this->actingAs($employeeA)->frontend()
            ->postJson("/api/v1/companies/{$companyA->id}/applications", [
                'candidate_id' => $candidateB->id,
                'job_order_id' => $jobA,
            ])
            ->assertStatus(422);

        $count = $this->inCompany($companyA, fn () => CandidateApplication::query()->count());
        $this->assertSame(0, $count);
    }

    public function test_stage_move_is_recorded_in_history_and_audited(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->createCandidate($company, $employee);
        $applicationId = $this->startApplication($company, $employee, $candidateId, $this->openJob($company, $employee));

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/applications/{$applicationId}/stage", [
                'stage_id' => $this->stageId($company, 'Interview'),
                'note' => 'Passed phone screen',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertSame(2, $this->historyCount($company, $applicationId));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application.stage_changed',
            'company_id' => $company->id,
            'target_id' => $applicationId,
        ]);
    }

    public function test_moving_to_terminal_stage_places_candidate_and_closes_the_application(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->createCandidate($company, $employee);
        $applicationId = $this->startApplication($company, $employee, $candidateId, $this->openJob($company, $employee));

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/applications/{$applicationId}/stage", [
                'stage_id' => $this->stageId($company, 'Deployment'),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'placed');

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/applications/{$applicationId}/stage", [
                'stage_id' => $this->stageId($company, 'Interview'),
            ])
            ->assertStatus(422);
    }

    public function test_stage_of_another_company_cannot_be_used(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $candidateId = $this->createCandidate($companyA, $employeeA);
        $applicationId = $this->startApplication($companyA, $employeeA, $candidateId, $this->openJob($companyA, $employeeA));

        $this->actingAs($employeeA)->frontend()
            ->patchJson("/api/v1/companies/{$companyA->id}/applications/{$applicationId}/stage", [
                'stage_id' => $this->stageId($companyB, 'Interview'),
            ])
            ->assertStatus(422);

        $this->assertSame(1, $this->historyCount($companyA, $applicationId));
    }

    public function test_stage_with_active_applications_cannot_be_disabled(): void
    {
        $company = $this->makeCompany();
        $admin = $this->adminOf($company);
        $candidateId = $this->createCandidate($company, $admin);
        $this->startApplication($company, $admin, $candidateId, $this->openJob($company, $admin));

        $this->actingAs($admin)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/pipeline-stages/{$this->stageId($company, 'Screening')}", [
                'enabled' => false,
            ])
            ->assertStatus(422);
    }

    public function test_history_rows_cannot_be_changed_even_by_the_runtime_role(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->createCandidate($company, $employee);
        $applicationId = $this->startApplication($company, $employee, $candidateId, $this->openJob($company, $employee));

        $historyId = (int) $this->inCompany($company, fn () => StageHistory::query()
            ->where('application_id', $applicationId)
            ->value('id'));

        $this->expectException(QueryException::class);

        $this->inCompany($company, fn () => DB::transaction(fn () => DB::table('stage_history')
            ->where('id', $historyId)
            ->update(['note' => 'tampered'])));
    }

    public function test_candidate_rows_are_invisible_without_company_context(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $id = $this->createCandidate($company, $employee);

        app(TenantDatabaseContext::class)->clear();

        $this->assertSame(0, DB::table('candidates')->where('id', $id)->count());
    }
}
