<?php

namespace App\Modules\Cv\Models;

use App\Modules\Tenancy\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $candidate_id
 * @property string $template_key
 * @property array<string, mixed> $content
 * @property list<string> $section_order
 * @property array<string, bool> $section_visibility
 * @property string $status
 * @property string|null $content_hash
 * @property int $created_by
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 */
class CvDraft extends Model
{
    use BelongsToCompany, HasUuids;

    public const TEMPLATES = ['classic', 'modern', 'compact'];

    public const SECTIONS = ['personal', 'summary', 'experience', 'education', 'skills', 'languages'];

    public const OPTIONAL_SECTIONS = ['summary', 'experience', 'education', 'skills', 'languages'];

    public const DEFAULT_ORDER = ['personal', 'summary', 'experience', 'education', 'skills', 'languages'];

    public const DEFAULT_VISIBILITY = [
        'summary' => true,
        'experience' => true,
        'education' => true,
        'skills' => true,
        'languages' => true,
    ];

    // Set only by the service through forceFill, never from request input.
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'section_order' => 'array',
            'section_visibility' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }
}
