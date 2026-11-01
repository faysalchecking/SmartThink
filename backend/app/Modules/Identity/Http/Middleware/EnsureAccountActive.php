<?php

namespace App\Modules\Identity\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A session belonging to a disabled account stops working on the next request.
 */
final class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->status !== 'active') {
            auth('web')->logout();
            $request->session()->invalidate();

            throw new AuthenticationException('Account is not active.');
        }

        return $next($request);
    }
}
