<?php

namespace App\Modules\Recruitment\Http\Resources;

use App\Modules\Recruitment\Models\CandidateApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CandidateApplication $application */
        $application = $this->resource;

        return [
            'id' => $application->id,
            'candidate_id' => $application->candidate_id,
            'job_order_id' => $application->job_order_id,
            'stage_id' => $application->stage_id,
            'status' => $application->status,
        ];
    }
}
