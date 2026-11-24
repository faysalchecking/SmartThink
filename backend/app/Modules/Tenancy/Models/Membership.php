<?php

namespace App\Modules\Tenancy\Models;

use App\Models\User;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Identity table, not tenant data. Access is decided by ResolveCompany (see D-19).
 *
 * @property string $id
 * @property string $company_id
 * @property int $user_id
 * @property CompanyRole $role
 * @property MembershipStatus $status
 * @property bool $can_export
 * @property-read Company $company
 * @property-read User $user
 */
class Membership extends Model
{
    use HasUuids;

    protected $fillable = ['company_id', 'user_id', 'role', 'status'];

    protected function casts(): array
    {
        return [
            'role' => CompanyRole::class,
            'status' => MembershipStatus::class,
            'can_export' => 'boolean',
        ];
    }

    /**
     * Company admins can always export. Employees need the flag, which only an admin can set (D-47).
     */
    public function canExport(): bool
    {
        return $this->role->isCompanyAdmin() || $this->can_export;
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
