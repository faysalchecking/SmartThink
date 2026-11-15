<?php

namespace App\Modules\Recruitment\Models;

use App\Modules\Tenancy\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only. The database trigger and REVOKE enforce the same rule for the runtime role (D-33).
 *
 * @property int $id
 * @property string $company_id
 * @property string $application_id
 * @property string|null $from_stage_id
 * @property string $to_stage_id
 * @property int $changed_by
 * @property Carbon $changed_at
 * @property string|null $note
 */
class StageHistory extends Model
{
    use BelongsToCompany;

    protected $table = 'stage_history';

    public $timestamps = false;

    protected $fillable = ['application_id', 'from_stage_id', 'to_stage_id', 'changed_by', 'changed_at', 'note'];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Stage history is append-only.'));
        static::deleting(fn () => throw new LogicException('Stage history is append-only.'));
    }
}
