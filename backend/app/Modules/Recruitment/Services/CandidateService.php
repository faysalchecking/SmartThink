<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Recruitment\Models\Candidate;
use Illuminate\Support\Facades\DB;

final class CandidateService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $actorId, string $companyId): Candidate
    {
        return DB::transaction(function () use ($data, $actorId, $companyId): Candidate {
            $candidate = Candidate::query()->create($data + ['created_by' => $actorId]);

            // Audit metadata lists field names only, never values, so no personal data is copied into logs.
            $this->audit->record('candidate.created', 'success', 'candidate', $candidate->id, $actorId, $companyId);

            return $candidate;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Candidate $candidate, array $data, int $actorId, string $companyId): Candidate
    {
        return DB::transaction(function () use ($candidate, $data, $actorId, $companyId): Candidate {
            $candidate->update($data);

            $this->audit->record(
                'candidate.updated',
                'success',
                'candidate',
                $candidate->id,
                $actorId,
                $companyId,
                ['fields' => array_keys($data)],
            );

            return $candidate;
        });
    }

    public function archive(Candidate $candidate, int $actorId, string $companyId): Candidate
    {
        if ($candidate->archived_at !== null) {
            return $candidate;
        }

        return DB::transaction(function () use ($candidate, $actorId, $companyId): Candidate {
            // Set directly, not through mass assignment, so archived_at stays out of $fillable.
            $candidate->archived_at = now();
            $candidate->save();

            $this->audit->record('candidate.archived', 'success', 'candidate', $candidate->id, $actorId, $companyId);

            return $candidate;
        });
    }
}
