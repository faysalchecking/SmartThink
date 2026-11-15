<?php

namespace App\Modules\Recruitment\Http\Resources;

use App\Modules\Recruitment\Models\StageHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StageHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StageHistory $row */
        $row = $this->resource;

        return [
            'id' => $row->id,
            'application_id' => $row->application_id,
            'from_stage_id' => $row->from_stage_id,
            'to_stage_id' => $row->to_stage_id,
            'changed_at' => $row->changed_at->toIso8601String(),
            'note' => $row->note,
        ];
    }
}
