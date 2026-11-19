<?php

namespace App\Modules\Cv\Http\Resources;

use App\Modules\Cv\Models\CvDraft;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CvDraftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CvDraft $draft */
        $draft = $this->resource;

        return [
            'id' => $draft->id,
            'candidate_id' => $draft->candidate_id,
            'template_key' => $draft->template_key,
            'status' => $draft->status,
            'reviewed' => $draft->status === 'reviewed',
            'reviewed_at' => $draft->reviewed_at?->toIso8601String(),
            'content' => $draft->content,
            'section_order' => $draft->section_order,
            'section_visibility' => $draft->section_visibility,
        ];
    }
}
