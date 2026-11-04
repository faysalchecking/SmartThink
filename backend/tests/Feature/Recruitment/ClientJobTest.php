<?php

namespace Tests\Feature\Recruitment;

use App\Models\User;
use App\Modules\Recruitment\Models\Client;
use App\Modules\Recruitment\Models\JobOrder;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Models\Company;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientJobTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array{0: Company, 1: User} */
    private function companyWithEmployee(string $name): array
    {
        $company = $this->makeCompany(name: $name);
        $employee = $this->makeUser();
        $this->makeMembership($company, $employee, CompanyRole::Employee);

        return [$company, $employee];
    }

    public function test_employee_creates_a_client_in_own_company(): void
    {
        [$company, $employee] = $this->companyWithEmployee('A');

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/clients", ['name' => 'Gulf Staffing', 'country' => 'SA'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Gulf Staffing');

        $this->assertDatabaseHas('audit_logs', ['action' => 'client.created', 'company_id' => $company->id]);
    }

    public function test_client_of_another_company_is_not_found(): void
    {
        [$companyA, $employeeA] = $this->companyWithEmployee('A');
        [$companyB] = $this->companyWithEmployee('B');

        $clientB = $this->inCompany($companyB, fn () => Client::query()->create(['name' => 'Secret Client']));

        $this->actingAs($employeeA)->frontend()
            ->getJson("/api/v1/companies/{$companyA->id}/clients/{$clientB->id}")
            ->assertNotFound();

        $this->actingAs($employeeA)->frontend()
            ->patchJson("/api/v1/companies/{$companyA->id}/clients/{$clientB->id}", ['name' => 'Hijacked'])
            ->assertNotFound();

        $name = $this->inCompany($companyB, fn () => Client::query()->whereKey($clientB->id)->value('name'));
        $this->assertSame('Secret Client', $name);
    }

    public function test_company_id_in_body_is_ignored_when_creating_a_client(): void
    {
        [$companyA, $employeeA] = $this->companyWithEmployee('A');
        [$companyB] = $this->companyWithEmployee('B');

        $this->actingAs($employeeA)->frontend()
            ->postJson("/api/v1/companies/{$companyA->id}/clients", [
                'name' => 'Body Trick',
                'company_id' => $companyB->id,
            ])
            ->assertCreated();

        $owner = $this->inCompany($companyA, fn () => Client::query()->where('name', 'Body Trick')->value('company_id'));
        $this->assertSame($companyA->id, $owner);

        $inB = $this->inCompany($companyB, fn () => Client::query()->where('name', 'Body Trick')->exists());
        $this->assertFalse($inB);
    }

    public function test_job_can_reference_a_client_of_the_same_company(): void
    {
        [$company, $employee] = $this->companyWithEmployee('A');

        $clientId = $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/clients", ['name' => 'Client One'])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/jobs", [
                'client_id' => $clientId,
                'title' => 'Driver',
                'openings' => 5,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_job_cannot_reference_a_client_of_another_company(): void
    {
        [$companyA, $employeeA] = $this->companyWithEmployee('A');
        [$companyB] = $this->companyWithEmployee('B');

        $clientB = $this->inCompany($companyB, fn () => Client::query()->create(['name' => 'Other Client']));

        $this->actingAs($employeeA)->frontend()
            ->postJson("/api/v1/companies/{$companyA->id}/jobs", [
                'client_id' => $clientB->id,
                'title' => 'Cross company job',
            ])
            ->assertStatus(422);

        $exists = $this->inCompany($companyA, fn () => JobOrder::query()->where('title', 'Cross company job')->exists());
        $this->assertFalse($exists);
    }

    public function test_database_refuses_a_job_pointing_at_another_companys_client(): void
    {
        [$companyA, $employeeA] = $this->companyWithEmployee('A');
        [$companyB] = $this->companyWithEmployee('B');

        $clientB = $this->inCompany($companyB, fn () => Client::query()->create(['name' => 'B client']));

        $this->expectException(QueryException::class);

        $this->inCompany($companyA, fn () => DB::transaction(fn () => DB::table('job_orders')->insert([
            'id' => (string) Str::uuid7(),
            'company_id' => $companyA->id,
            'client_id' => $clientB->id,
            'title' => 'Forged',
            'status' => 'draft',
            'openings' => 1,
            'created_by' => $employeeA->id,
            'created_at' => now(),
            'updated_at' => now(),
        ])));
    }

    public function test_invalid_job_status_is_rejected_and_valid_status_is_accepted(): void
    {
        [$company, $employee] = $this->companyWithEmployee('A');

        // Created directly, so this test checks only the status rules on PATCH.
        $jobId = $this->inCompany($company, function () use ($employee) {
            $client = Client::query()->create(['name' => 'Client Two']);

            return JobOrder::query()->create([
                'client_id' => $client->id,
                'title' => 'Cook',
                'created_by' => $employee->id,
            ])->id;
        });

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/jobs/{$jobId}", ['status' => 'archived'])
            ->assertStatus(422);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/jobs/{$jobId}", ['status' => 'open'])
            ->assertOk()
            ->assertJsonPath('data.status', 'open');
    }
}
