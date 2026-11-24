<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recruitment\Models\CandidateApplication;
use App\Modules\Recruitment\Models\JobOrder;
use App\Modules\Recruitment\Models\PipelineStage;
use Illuminate\Http\JsonResponse;

final class ReportController extends Controller
{
    /**
     * Active applications per job and per stage. Placed, rejected and withdrawn are excluded.
     * Every query runs inside the company scope, so counts cannot include other companies.
     */
    public function pipeline(): JsonResponse
    {
        $counts = CandidateApplication::query()
            ->where('status', 'active')
            ->groupBy('job_order_id', 'stage_id')
            ->select('job_order_id', 'stage_id')
            ->selectRaw('count(*) as total')
            ->get();

        $jobTitles = JobOrder::query()
            ->whereIn('id', $counts->pluck('job_order_id')->unique()->values()->all())
            ->pluck('title', 'id');

        $stages = PipelineStage::query()->get(['id', 'name', 'position'])->keyBy('id');

        $rows = $counts->map(function (CandidateApplication $row) use ($jobTitles, $stages): array {
            $stage = $stages->get($row->stage_id);

            return [
                'job_order_id' => $row->job_order_id,
                'job_title' => (string) ($jobTitles->get($row->job_order_id) ?? ''),
                'stage_id' => $row->stage_id,
                'stage_name' => $stage === null ? '' : (string) $stage->name,
                'stage_position' => $stage === null ? 0 : (int) $stage->position,
                'total' => (int) $row->getAttribute('total'),
            ];
        });

        $data = $rows
            ->sortBy('stage_position')
            ->sortBy('job_title')
            ->values()
            ->map(fn (array $row): array => [
                'job_order_id' => $row['job_order_id'],
                'job_title' => $row['job_title'],
                'stage_id' => $row['stage_id'],
                'stage_name' => $row['stage_name'],
                'total' => $row['total'],
            ])
            ->all();

        return response()->json(['data' => $data]);
    }
}
