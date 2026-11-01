<?php

namespace App\Modules\Tenancy\Http\Middleware;

use App\Models\User;
use App\Modules\Tenancy\Context\TenantContext;
use App\Modules\Tenancy\Context\TenantDatabaseContext;
use App\Modules\Tenancy\Enums\CompanyStatus;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Models\Membership;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

final class ResolveCompany
{
    public const ATTRIBUTE = 'membership';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $companyId = (string) $request->route('company');

        // Unknown, foreign, and malformed IDs all look the same: not found.
        if (! $user instanceof User || ! Str::isUuid($companyId)) {
            abort(404);
        }

        $membership = Membership::query()
            ->with('company')
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->where('status', MembershipStatus::Active->value)
            ->first();

        if ($membership === null) {
            abort(404);
        }

        // Members of a suspended or pending company are told why, without any company data.
        if ($membership->company->status !== CompanyStatus::Active) {
            abort(403);
        }

        $request->attributes->set(self::ATTRIBUTE, $membership);

        $tenant = app(TenantContext::class);
        $database = app(TenantDatabaseContext::class);

        $tenant->set($membership->company_id);
        $database->apply($membership->company_id);

        try {
            return $next($request);
        } finally {
            $tenant->clear();
            $database->clear();
        }
    }

    public static function membership(Request $request): Membership
    {
        $membership = $request->attributes->get(self::ATTRIBUTE);

        if (! $membership instanceof Membership) {
            throw new LogicException('ResolveCompany middleware must run before this code.');
        }

        return $membership;
    }
}
