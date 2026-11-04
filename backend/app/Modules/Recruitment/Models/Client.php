<?php

namespace App\Modules\Recruitment\Models;

use App\Modules\Tenancy\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property string|null $country
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property Carbon|null $archived_at
 */
class Client extends Model
{
    use BelongsToCompany, HasUuids;

    // company_id is deliberately not fillable.
    protected $fillable = ['name', 'country', 'contact_name', 'contact_email'];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }
}
