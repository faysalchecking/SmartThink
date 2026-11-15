<?php

namespace Tests\Feature\Recruitment;

use App\Models\User;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Recruitment\Models\CandidateApplication;
use App\Modules\Recruitment\Models\Client;
use App\Modules\Recruitment\Models\Interview;
use App\Modules\Recruitment\Models\JobOrder;
use App\Modules\Recruitment\Models\PipelineStage;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class InterviewDashboardTest extends TestCase
{
    use DatabaseTransactions;

    private function employeeOf(Company $company): User
    {
        $user = $this->makeUser();
        $this->makeMembership($company, $user, CompanyRole::Employee);

        return $user;
    }

    /**
     * Creates a candidate, job, and application directly inside the company.
     */
    private function applicationIn(Company $company, User $actor): string
    {
        return (string) $this->inCompany($company, function () use ($actor): string {
            $candidate = Candidate::query()->create([
                'full_name' => 'Interview Candidate',
                'created_by' => $actor->id,
            ]);

            $client = Client::query()->create(['name' => 'Client X']);

            $job = JobOrder::query()->create([
                'client_id' => $client->id,
                'title' => 'Driver',
                'status' => 'open',
                'created_by' => $actor->id,
            ]);

            $stage = PipelineStage::query()->where('name', 'Screening')->firstOrFail();

            return (string) CandidateApplication::query()->create([
                'candidate_id' => $candidate->id,
                'job_order_id' => $job->id,
                'stage_id' => $stage->id,
                'status' => 'active',
                'created_by' => $actor->id,
            ])->id;
        });
    }

    /**
     * Creates an interview directly inside the company (used for cross-company setup).
     */
    private function interviewIn(Company $company, User $actor, string $applicationId): string
    {
        return (string) $this->inCompany($company, fn (): string => (string) Interview::query()->create([
            'application_id' => $applicationId,
            'interviewer_id' => $actor->id,
            'scheduled_at' => now()->addDay(),
            'result' => 'pending',
            'created_by' => $actor->id,
        ])->id);
    }

    /**
     * @param  TestResponse<Response>  $response
     * @return list<string>
     */
    private function idsIn(TestResponse $response): array
    {
        $ids = [];

        foreach ((array) $response->json('data') as $row) {
            if (is_array($row) && isset($row['id'])) {
                $ids[] = (string) $row['id'];
            }
        }

        return $ids;
    }

    /**
     * @param  array<mixed>  $stages
     */
    private function stageTotal(array $stages, string $name): int
    {
        foreach ($stages as $stage) {
            if (is_array($stage) && ($stage['name'] ?? null) === $name) {
                return (int) ($stage['total'] ?? -1);
            }
        }

        return -1;
    }

    public function test_employee_schedules_an_interview_for_an_active_application(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $applicationId = $this->applicationIn($company, $employee);

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/interviews", [
                'application_id' => $applicationId,
                'interviewer_id' => $employee->id,
                'scheduled_at' => now()->addDays(2)->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.result', 'pending');

        $this->assertDatabaseHas('audit_logs', ['action' => 'interview.scheduled', 'company_id' => $company->id]);
    }

    public function test_interviewer_from_another_company_is_rejected(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $outsider = $this->employeeOf($companyB);
        $applicationId = $this->applicationIn($companyA, $employeeA);

        $this->actingAs($employeeA)->frontend()
            ->postJson("/api/v1/companies/{$companyA->id}/interviews", [
                'application_id' => $applicationId,
                'interviewer_id' => $outsider->id,
                'scheduled_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertStatus(422);
    }

    public function test_application_of_another_company_cannot_be_used(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $employeeB = $this->employeeOf($companyB);
        $applicationB = $this->applicationIn($companyB, $employeeB);

        $this->actingAs($employeeA)->frontend()
            ->postJson("/api/v1/companies/{$companyA->id}/interviews", [
                'application_id' => $applicationB,
                'interviewer_id' => $employeeA->id,
                'scheduled_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertStatus(422);
    }

    public function test_interview_cannot_be_scheduled_for_a_placed_application(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $applicationId = $this->applicationIn($company, $employee);

        $deploymentId = (string) $this->inCompany(
            $company,
            fn () => PipelineStage::query()->where('name', 'Deployment')->value('id'),
        );

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/applications/{$applicationId}/stage", [
                'stage_id' => $deploymentId,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'placed');

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/interviews", [
                'application_id' => $applicationId,
                'interviewer_id' => $employee->id,
                'scheduled_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertStatus(422);
    }

    public function test_result_can_be_recorded_and_is_audited(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $applicationId = $this->applicationIn($company, $employee);

        $interviewId = (string) $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/interviews", [
                'application_id' => $applicationId,
                'interviewer_id' => $employee->id,
                'scheduled_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/interviews/{$interviewId}", ['result' => 'passed'])
            ->assertOk()
            ->assertJsonPath('data.result', 'passed');

        $this->assertDatabaseHas('audit_logs', ['action' => 'interview.updated', 'target_id' => $interviewId]);
    }

    public function test_unknown_result_value_is_rejected(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $applicationId = $this->applicationIn($company, $employee);

        $interviewId = (string) $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/interviews", [
                'application_id' => $applicationId,
                'interviewer_id' => $employee->id,
                'scheduled_at' => now()->addDay()->toIso8601String(),
            ])
            ->json('data.id');

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/interviews/{$interviewId}", ['result' => 'maybe'])
            ->assertStatus(422);
    }

    public function test_interview_of_another_company_is_not_found(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $employeeB = $this->employeeOf($companyB);
        $applicationA = $this->applicationIn($companyA, $employeeA);
        $interviewA = $this->interviewIn($companyA, $employeeA, $applicationA);

        // Employee B uses company B's URL with company A's interview ID.
        $this->actingAs($employeeB)->frontend()
            ->patchJson("/api/v1/companies/{$companyB->id}/interviews/{$interviewA}", ['result' => 'failed'])
            ->assertNotFound();

        $result = $this->inCompany($companyA, fn () => Interview::query()->whereKey($interviewA)->value('result'));
        $this->assertSame('pending', $result);
    }

    public function test_interview_list_shows_only_own_company(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $employeeB = $this->employeeOf($companyB);

        $interviewA = $this->interviewIn($companyA, $employeeA, $this->applicationIn($companyA, $employeeA));
        $interviewB = $this->interviewIn($companyB, $employeeB, $this->applicationIn($companyB, $employeeB));

        $ids = $this->idsIn($this->actingAs($employeeA)->frontend()
            ->getJson("/api/v1/companies/{$companyA->id}/interviews"));

        $this->assertContains($interviewA, $ids);
        $this->assertNotContains($interviewB, $ids);
    }

    public function test_dashboard_counts_are_exact_and_exclude_other_companies(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $employeeB = $this->employeeOf($companyB);

        $this->applicationIn($companyA, $employeeA);
        $this->applicationIn($companyB, $employeeB);
        $this->applicationIn($companyB, $employeeB);

        $response = $this->actingAs($employeeA)->frontend()
            ->getJson("/api/v1/companies/{$companyA->id}/dashboard")
            ->assertOk();

        $response->assertJsonPath('data.candidates_active', 1);
        $response->assertJsonPath('data.applications_active', 1);
        $response->assertJsonPath('data.jobs_open', 1);

        $stages = (array) $response->json('data.stages');
        $this->assertSame(1, $this->stageTotal($stages, 'Screening'));
        $this->assertSame(0, $this->stageTotal($stages, 'Medical'));
    }

    public function test_update_of_unknown_interview_returns_not_found(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/interviews/00000000-0000-7000-8000-000000000000", ['result' => 'passed'])
            ->assertNotFound();
    }
}
