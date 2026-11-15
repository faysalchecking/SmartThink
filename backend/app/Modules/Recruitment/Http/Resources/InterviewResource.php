<?php

namespace App\Modules\Recruitment\Http\Resources;

use App\Modules\Recruitment\Models\Interview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InterviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Interview $interview */
        $interview = $this->resource;

        return [
            'id' => $interview->id,
            'application_id' => $interview->application_id,
            'interviewer_id' => $interview->interviewer_id,
            'scheduled_at' => $interview->scheduled_at->toIso8601String(),
            'result' => $interview->result,
            'notes' => $interview->notes,
        ];
    }
}
