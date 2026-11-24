<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use App\Modules\Tenancy\Http\Requests\UpdateMembershipRequest;
use App\Modules\Tenancy\Http\Resources\MembershipResource;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class MemberController extends Controller
{
    public function __construct(private readonly MembershipService $memberships) {}

    public function index(Request $request): JsonResponse
    {
        $this->requireCompanyAdmin($request);
        $companyId = ResolveCompany::membership($request)->company_id;
        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        $members = Membership::query()
            ->with('user')
            ->where('company_id', $companyId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($perPage);

        return MembershipResource::collection($members)->response();
    }

    public function update(UpdateMembershipRequest $request): JsonResponse
    {
        // Read the member ID by name from the route. Do not rely on argument order.
        $member = (string) $request->route('member');

        if (! Str::isUuid($member)) {
            abort(404);
        }

        $companyId = ResolveCompany::membership($request)->company_id;

        $target = Membership::query()
            ->where('company_id', $companyId)
            ->where('id', $member)
            ->firstOrFail();

        $data = $request->validated();

        $updated = $this->memberships->update(
            membership: $target,
            role: isset($data['role']) ? CompanyRole::from((string) $data['role']) : null,
            status: isset($data['status']) ? MembershipStatus::from((string) $data['status']) : null,
            actorId: $this->actorId($request),
        );

        return MembershipResource::make($updated)->response();
    }

    /**
     * Grants or removes the export permission. Only a company admin can call this (D-47).
     */
    public function setExportPermission(Request $request, AuditLogger $audit): JsonResponse
    {
        $admin = $this->requireCompanyAdmin($request);
        $data = $request->validate(['can_export' => ['required', 'boolean']]);
        $memberId = $this->routeUuid($request, 'member');

        $target = Membership::query()
            ->where('company_id', $admin->company_id)
            ->where('id', $memberId)
            ->firstOrFail();

        $target->forceFill(['can_export' => (bool) $data['can_export']])->save();

        $audit->record(
            action: 'membership.export_permission_changed',
            result: 'success',
            targetType: 'membership',
            targetId: $target->id,
            actorId: $this->actorId($request),
            companyId: $admin->company_id,
            metadata: ['can_export' => (bool) $data['can_export']],
        );

        return MembershipResource::make($target)->response();
    }
}
