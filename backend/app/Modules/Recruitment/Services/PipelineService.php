<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Recruitment\Models\CandidateApplication;
use App\Modules\Recruitment\Models\JobOrder;
use App\Modules\Recruitment\Models\PipelineStage;
use App\Modules\Recruitment\Models\StageHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PipelineService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createStage(array $data, int $actorId, string $companyId): PipelineStage
    {
        return DB::transaction(function () use ($data, $actorId, $companyId): PipelineStage {
            $position = (int) PipelineStage::query()->max('position') + 1;

            $stage = PipelineStage::query()->create($data + [
                'position' => $position,
                'is_terminal' => false,
                'enabled' => true,
            ]);

            $this->audit->record(
                'pipeline_stage.created',
                'success',
                'pipeline_stage',
                $stage->id,
                $actorId,
                $companyId,
                ['kind' => $stage->kind],
            );

            return $stage;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStage(PipelineStage $stage, array $data, int $actorId, string $companyId): PipelineStage
    {
        return DB::transaction(function () use ($stage, $data, $actorId, $companyId): PipelineStage {
            $locked = PipelineStage::query()->whereKey($stage->id)->lockForUpdate()->firstOrFail();

            if (($data['enabled'] ?? true) === false && $locked->enabled) {
                $inUse = CandidateApplication::query()
                    ->where('stage_id', $locked->id)
                    ->where('status', 'active')
                    ->exists();

                if ($inUse) {
                    throw ValidationException::withMessages([
                        'enabled' => 'Active applications are in this stage. Move them to another stage first.',
                    ]);
                }
            }

            $locked->update($data);

            $this->audit->record(
                'pipeline_stage.updated',
                'success',
                'pipeline_stage',
                $locked->id,
                $actorId,
                $companyId,
                ['fields' => array_keys($data)],
            );

            return $locked;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function startApplication(array $data, int $actorId, string $companyId): CandidateApplication
    {
        // Lookups run through the company scope, so another company's rows are simply not found.
        $candidate = Candidate::query()
            ->whereKey((string) $data['candidate_id'])
            ->whereNull('archived_at')
            ->first();

        if ($candidate === null) {
            throw $this->invalid('candidate_id', 'The selected candidate does not exist or is archived.');
        }

        $job = JobOrder::query()->whereKey((string) $data['job_order_id'])->first();

        if ($job === null) {
            throw $this->invalid('job_order_id', 'The selected job order does not exist.');
        }

        if ($job->status === 'closed') {
            throw $this->invalid('job_order_id', 'This job order is closed.');
        }

        $duplicate = CandidateApplication::query()
            ->where('candidate_id', $candidate->id)
            ->where('job_order_id', $job->id)
            ->exists();

        if ($duplicate) {
            throw $this->invalid('job_order_id', 'This candidate already applied to this job.');
        }

        $firstStage = PipelineStage::query()
            ->where('enabled', true)
            ->orderBy('position')
            ->first();

        if ($firstStage === null) {
            throw $this->invalid('job_order_id', 'No pipeline stage is enabled for this company.');
        }

        return DB::transaction(function () use ($candidate, $job, $firstStage, $actorId, $companyId): CandidateApplication {
            $application = CandidateApplication::query()->create([
                'candidate_id' => $candidate->id,
                'job_order_id' => $job->id,
                'stage_id' => $firstStage->id,
                'status' => 'active',
                'created_by' => $actorId,
            ]);

            StageHistory::query()->create([
                'application_id' => $application->id,
                'from_stage_id' => null,
                'to_stage_id' => $firstStage->id,
                'changed_by' => $actorId,
                'changed_at' => now(),
                'note' => 'Application created',
            ]);

            $this->audit->record(
                'application.created',
                'success',
                'candidate_application',
                $application->id,
                $actorId,
                $companyId,
                ['stage' => $firstStage->name],
            );

            return $application;
        });
    }

    public function moveStage(
        CandidateApplication $application,
        string $stageId,
        ?string $note,
        int $actorId,
        string $companyId,
    ): CandidateApplication {
        return DB::transaction(function () use ($application, $stageId, $note, $actorId, $companyId): CandidateApplication {
            $locked = CandidateApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'active') {
                throw $this->invalid('stage_id', 'This application is closed and cannot change stage.');
            }

            $target = PipelineStage::query()->whereKey($stageId)->first();

            if ($target === null || ! $target->enabled) {
                throw $this->invalid('stage_id', 'The selected stage is not available.');
            }

            if ($target->id === $locked->stage_id) {
                throw $this->invalid('stage_id', 'The application is already in this stage.');
            }

            $from = $locked->stage_id;

            $locked->stage_id = $target->id;
            if ($target->is_terminal) {
                $locked->status = 'placed';
            }
            $locked->save();

            StageHistory::query()->create([
                'application_id' => $locked->id,
                'from_stage_id' => $from,
                'to_stage_id' => $target->id,
                'changed_by' => $actorId,
                'changed_at' => now(),
                'note' => $note,
            ]);

            $this->audit->record(
                'application.stage_changed',
                'success',
                'candidate_application',
                $locked->id,
                $actorId,
                $companyId,
                ['to' => $target->name, 'status' => $locked->status],
            );

            return $locked;
        });
    }

    private function invalid(string $field, string $message): ValidationException
    {
        return ValidationException::withMessages([$field => $message]);
    }
}
