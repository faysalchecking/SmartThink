<?php

namespace App\Modules\Recruitment\Models;

use App\Modules\Tenancy\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property int $position
 * @property string $kind
 * @property bool $is_terminal
 * @property bool $enabled
 */
class PipelineStage extends Model
{
    use BelongsToCompany, HasUuids;

    public const KINDS = ['standard', 'overseas'];

    protected $fillable = ['name', 'position', 'kind', 'is_terminal', 'enabled'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_terminal' => 'boolean',
            'enabled' => 'boolean',
        ];
    }
}
