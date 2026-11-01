<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use App\Modules\Tenancy\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
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
}
