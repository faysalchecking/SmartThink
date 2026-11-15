<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use App\Modules\Tenancy\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;
use LogicException;

abstract class Controller extends BaseController
{
    protected function actorId(Request $request): int
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new LogicException('An authenticated user is required for this action.');
        }

        return $user->id;
    }

    protected function requireCompanyAdmin(Request $request): Membership
    {
        $membership = ResolveCompany::membership($request);

        if (! $membership->role->isCompanyAdmin()) {
            abort(403);
        }

        return $membership;
    }

    /**
     * Reads a UUID route parameter by name. Malformed values are treated as not found.
     * Never read route values by controller argument position.
     */
    protected function routeUuid(Request $request, string $name): string
    {
        $value = (string) $request->route($name);

        if (! Str::isUuid($value)) {
            abort(404);
        }

        return $value;
    }
}
