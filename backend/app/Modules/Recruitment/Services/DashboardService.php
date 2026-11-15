<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Recruitment\Models\CandidateApplication;
use App\Modules\Recruitment\Models\Interview;
use App\Modules\Recruitment\Models\JobOrder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * Every count is computed inside the current company context, so the numbers
 * cannot include another company's rows (TI-014).
 */
final class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(string $companyId): array
    {
        $stages = DB::table('pipeline_stages as s')
            ->leftJoin('candidate_applications as a', function (JoinClause $join): void {
                $join->on('a.stage_id', '=', 's.id')->where('a.status', '=', 'active');
            })
            ->where('s.company_id', $companyId)
            ->where('s.enabled', true)
            ->groupBy('s.id', 's.name', 's.kind', 's.position')
            ->orderBy('s.position')
            ->get(['s.name', 's.kind', DB::raw('count(a.id) as total')])
            ->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'kind' => (string) $row->kind,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        return [
            'candidates_active' => Candidate::query()->whereNull('archived_at')->count(),
            'applications_active' => CandidateApplication::query()->where('status', 'active')->count(),
            'applications_placed' => CandidateApplication::query()->where('status', 'placed')->count(),
            'jobs_open' => JobOrder::query()->where('status', 'open')->count(),
            'interviews_upcoming' => Interview::query()
                ->where('result', 'pending')
                ->where('scheduled_at', '>=', now())
                ->count(),
            'stages' => $stages,
        ];
    }
}
