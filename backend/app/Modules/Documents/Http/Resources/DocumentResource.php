<?php

namespace App\Modules\Documents\Http\Resources;

use App\Modules\Documents\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Document $document */
        $document = $this->resource;

        return [
            'id' => $document->id,
            'candidate_id' => $document->candidate_id,
            'document_type' => $document->document_type,
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'review_status' => $document->review_status,
            'review_reason' => $document->review_reason,
            'archived' => $document->archived_at !== null,
        ];
    }
}
