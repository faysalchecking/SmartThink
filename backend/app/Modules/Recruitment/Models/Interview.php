<?php

namespace App\Modules\Recruitment\Models;

use App\Modules\Tenancy\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $application_id
 * @property int $interviewer_id
 * @property Carbon $scheduled_at
 * @property string $result
 * @property string|null $notes
 * @property int $created_by
 */
class Interview extends Model
{
    use BelongsToCompany, HasUuids;

    public const RESULTS = ['pending', 'passed', 'failed', 'no_show'];

    protected $fillable = ['application_id', 'interviewer_id', 'scheduled_at', 'result', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }
}
