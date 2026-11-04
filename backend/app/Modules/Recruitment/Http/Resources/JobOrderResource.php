<?php

namespace App\Modules\Recruitment\Http\Resources;

use App\Modules\Recruitment\Models\JobOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class JobOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var JobOrder $job */
        $job = $this->resource;

        return [
            'id' => $job->id,
            'client_id' => $job->client_id,
            'title' => $job->title,
            'country' => $job->country,
            'status' => $job->status,
            'openings' => $job->openings,
        ];
    }
}
