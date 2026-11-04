<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Recruitment\Models\Client;
use App\Modules\Recruitment\Models\JobOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecruitmentService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createClient(array $data, int $actorId, string $companyId): Client
    {
        return DB::transaction(function () use ($data, $actorId, $companyId): Client {
            $client = Client::query()->create($data);

            $this->audit->record('client.created', 'success', 'client', $client->id, $actorId, $companyId);

            return $client;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createJob(array $data, int $actorId, string $companyId): JobOrder
    {
        // The client is looked up through the company scope. A client of another company is simply not found.
        $this->assertClientInCompany((string) $data['client_id']);

        return DB::transaction(function () use ($data, $actorId, $companyId): JobOrder {
            $job = JobOrder::query()->create($data + ['status' => 'draft', 'created_by' => $actorId]);

            $this->audit->record('job.created', 'success', 'job_order', $job->id, $actorId, $companyId);

            return $job;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateJob(JobOrder $job, array $data, int $actorId, string $companyId): JobOrder
    {
        if (isset($data['client_id'])) {
            $this->assertClientInCompany((string) $data['client_id']);
        }

        return DB::transaction(function () use ($job, $data, $actorId, $companyId): JobOrder {
            $job->update($data);

            $this->audit->record(
                'job.updated',
                'success',
                'job_order',
                $job->id,
                $actorId,
                $companyId,
                ['fields' => array_keys($data)],
            );

            return $job;
        });
    }

    private function assertClientInCompany(string $clientId): void
    {
        if (Client::query()->whereKey($clientId)->doesntExist()) {
            throw ValidationException::withMessages(['client_id' => 'The selected client does not exist.']);
        }
    }
}
