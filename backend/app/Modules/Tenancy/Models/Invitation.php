<?php

namespace App\Modules\Tenancy\Models;

use App\Models\User;
use App\Modules\Tenancy\Enums\CompanyRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $email
 * @property CompanyRole $role
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $revoked_at
 * @property int $created_by
 * @property-read Company $company
 * @property-read User $creator
 */
class Invitation extends Model
{
    use HasUuids;

    protected $fillable = ['company_id', 'email', 'role', 'token_hash', 'expires_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'role' => CompanyRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
