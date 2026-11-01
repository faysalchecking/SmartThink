<?php

namespace App\Modules\Tenancy\Models;

use App\Modules\Tenancy\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property CompanyStatus $status
 */
class Company extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug', 'status'];

    protected function casts(): array
    {
        return ['status' => CompanyStatus::class];
    }
}
