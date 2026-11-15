<?php

namespace App\Modules\Recruitment\Http\Resources;

use App\Modules\Recruitment\Models\PipelineStage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PipelineStageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PipelineStage $stage */
        $stage = $this->resource;

        return [
            'id' => $stage->id,
            'name' => $stage->name,
            'position' => $stage->position,
            'kind' => $stage->kind,
            'is_terminal' => $stage->is_terminal,
            'enabled' => $stage->enabled,
        ];
    }
}
