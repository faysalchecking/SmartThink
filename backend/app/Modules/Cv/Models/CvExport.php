<?php

namespace App\Modules\Cv\Models;

use App\Modules\Tenancy\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $company_id
 * @property string $cv_draft_id
 * @property string $storage_key
 * @property string $sha256
 * @property int $size_bytes
 * @property string $content_hash
 * @property int $created_by
 */
class CvExport extends Model
{
    use BelongsToCompany, HasUuids;

    public $timestamps = false;

    // storage_key is never shown to clients. Values are set by the service with forceFill.
    protected $hidden = ['storage_key'];
}
