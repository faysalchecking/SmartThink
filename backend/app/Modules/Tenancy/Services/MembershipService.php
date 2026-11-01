<?php

namespace App\Modules\Tenancy\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Models\Membership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MembershipService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Changes role or status. Sessions of a deactivated member are not deleted here,
     * because ResolveCompany rejects them on every request (see D-23).
     */
    public function update(
        Membership $membership,
        ?CompanyRole $role,
        ?MembershipStatus $status,
        int $actorId,
    ): Membership {
        return DB::transaction(function () use ($membership, $role, $status, $actorId): Membership {
            // Lock all active admins of this company so two concurrent changes cannot remove the last one.
            $activeAdmins = Membership::query()
                ->where('company_id', $membership->company_id)
                ->where('role', CompanyRole::CompanyAdmin)
                ->where('status', MembershipStatus::Active)
                ->lockForUpdate()
                ->get();

            $wasActiveAdmin = $membership->role === CompanyRole::CompanyAdmin
                && $membership->status === MembershipStatus::Active;

            $targetRole = $role ?? $membership->role;
            $targetStatus = $status ?? $membership->status;

            $staysActiveAdmin = $targetRole === CompanyRole::CompanyAdmin
                && $targetStatus === MembershipStatus::Active;

            if ($wasActiveAdmin && ! $staysActiveAdmin && $activeAdmins->count() <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'The company must keep at least one active company admin.',
                ]);
            }

            $changed = [];
            if ($role !== null && $role !== $membership->role) {
                $membership->role = $role;
                $changed[] = 'role';
            }
            if ($status !== null && $status !== $membership->status) {
                $membership->status = $status;
                $changed[] = 'status';
            }

            if ($changed !== []) {
                $membership->save();

                $this->audit->record(
                    action: 'membership.updated',
                    result: 'success',
                    targetType: 'membership',
                    targetId: $membership->id,
                    actorId: $actorId,
                    companyId: $membership->company_id,
                    metadata: [
                        'fields' => $changed,
                        'role' => $membership->role->value,
                        'status' => $membership->status->value,
                    ],
                );
            }

            return $membership;
        });
    }
}
