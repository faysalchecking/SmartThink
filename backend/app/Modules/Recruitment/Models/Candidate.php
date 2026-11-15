<?php

namespace App\Modules\Recruitment\Models;

use App\Modules\Tenancy\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $full_name
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $date_of_birth
 * @property string|null $nationality
 * @property string|null $passport_number
 * @property string|null $summary
 * @property Carbon|null $archived_at
 */
class Candidate extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'date_of_birth',
        'nationality',
        'passport_number',
        'summary',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'passport_number' => 'encrypted',
            'archived_at' => 'datetime',
        ];
    }
}
