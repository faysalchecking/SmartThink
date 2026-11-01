<?php

namespace App\Http\Controllers;

use App\Models\User;
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
}
