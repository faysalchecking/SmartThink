<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Recruitment\Models\CandidateApplication;
use App\Modules\Recruitment\Models\Interview;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Models\Membership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InterviewService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(array $data, int $actorId, string $companyId): Interview
    {
        // Scoped lookup: an application of another company is simply not found.
        $application = CandidateApplication::query()->whereKey((string) $data['application_id'])->first();

        if ($application === null) {
            throw $this->invalid('application_id', 'The selected application does not exist.');
        }

        if ($application->status !== 'active') {
            throw $this->invalid('application_id', 'Interviews can only be scheduled for active applications.');
        }

        // The interviewer must be an active member of this same company.
        $interviewerIsMember = Membership::query()
            ->where('company_id', $companyId)
            ->where('user_id', (int) $data['interviewer_id'])
            ->where('status', MembershipStatus::Active)
            ->exists();

        if (! $interviewerIsMember) {
            throw $this->invalid('interviewer_id', 'The interviewer must be an active member of this company.');
        }

        return DB::transaction(function () use ($application, $data, $actorId, $companyId): Interview {
            $interview = Interview::query()->create([
                'application_id' => $application->id,
                'interviewer_id' => (int) $data['interviewer_id'],
                'scheduled_at' => Carbon::parse((string) $data['scheduled_at']),
                'result' => 'pending',
                'notes' => isset($data['notes']) ? (string) $data['notes'] : null,
                'created_by' => $actorId,
            ]);

            $this->audit->record(
                'interview.scheduled',
                'success',
                'interview',
                $interview->id,
                $actorId,
                $companyId,
            );

            return $interview;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Interview $interview, array $data, int $actorId, string $companyId): Interview
    {
        return DB::transaction(function () use ($interview, $data, $actorId, $companyId): Interview {
            $changed = [];

            if (array_key_exists('result', $data)) {
                $interview->result = (string) $data['result'];
                $changed[] = 'result';
            }

            if (array_key_exists('notes', $data)) {
                $interview->notes = $data['notes'] === null ? null : (string) $data['notes'];
                $changed[] = 'notes';
            }

            if (isset($data['scheduled_at'])) {
                $interview->scheduled_at = Carbon::parse((string) $data['scheduled_at']);
                $changed[] = 'scheduled_at';
            }

            if ($changed !== []) {
                $interview->save();

                $this->audit->record(
                    'interview.updated',
                    'success',
                    'interview',
                    $interview->id,
                    $actorId,
                    $companyId,
                    ['fields' => $changed],
                );
            }

            return $interview;
        });
    }

    private function invalid(string $field, string $message): ValidationException
    {
        return ValidationException::withMessages([$field => $message]);
    }
}
