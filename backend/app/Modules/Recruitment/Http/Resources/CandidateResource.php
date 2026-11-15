<?php

namespace App\Modules\Recruitment\Http\Resources;

use App\Modules\Recruitment\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CandidateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Candidate $candidate */
        $candidate = $this->resource;

        return [
            'id' => $candidate->id,
            'full_name' => $candidate->full_name,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'date_of_birth' => $candidate->date_of_birth?->toDateString(),
            'nationality' => $candidate->nationality,
            'passport_masked' => self::mask($candidate->passport_number),
            'summary' => $candidate->summary,
            'archived' => $candidate->archived_at !== null,
        ];
    }

    /**
     * Shows only the last three characters. The full number is not part of any API response yet.
     */
    private static function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 3).substr($value, -3);
    }
}
