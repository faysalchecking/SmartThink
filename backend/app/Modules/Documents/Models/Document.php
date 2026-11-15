<?php

namespace App\Modules\Documents\Models;

use App\Modules\Tenancy\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $candidate_id
 * @property string $document_type
 * @property string $storage_key
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $sha256
 * @property string $review_status
 * @property string|null $review_reason
 * @property int $uploaded_by
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $archived_at
 */
class Document extends Model
{
    use BelongsToCompany, HasUuids;

    public const TYPES = ['passport', 'medical', 'visa', 'certificate', 'other'];

    public const REVIEW_STATUSES = ['pending', 'verified', 'rejected'];

    // storage_key, uploaded_by and reviewed_by are set by the service, never from input.
    protected $fillable = ['candidate_id', 'document_type', 'original_name', 'mime_type', 'size_bytes', 'sha256'];

    protected $hidden = ['storage_key'];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'reviewed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
