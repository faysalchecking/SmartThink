<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class AuthController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $email = Str::lower($request->string('email')->toString());
        $password = $request->string('password')->toString();

        // The status condition means disabled accounts fail exactly like wrong passwords.
        $ok = Auth::guard('web')->attempt([
            'email' => $email,
            'password' => $password,
            'status' => 'active',
        ]);

        if (! $ok) {
            // Failed attempts store a hash of the email, never the email itself.
            $this->audit->record(
                action: 'auth.login',
                result: 'failed',
                targetType: 'user',
                metadata: ['email_hash' => hash('sha256', $email)],
            );

            return response()->json([
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Email or password is incorrect.',
                ],
            ], 401);
        }

        $request->session()->regenerate();

        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json(['error' => ['code' => 'INTERNAL', 'message' => 'Internal server error.']], 500);
        }

        $this->audit->record(
            action: 'auth.login',
            result: 'success',
            targetType: 'user',
            targetId: (string) $user->id,
            actorId: $user->id,
        );

        return UserResource::make($user)->response();
    }

    public function logout(Request $request): JsonResponse
    {
        $this->audit->record(
            action: 'auth.logout',
            result: 'success',
            targetType: 'user',
            targetId: (string) $this->actorId($request),
            actorId: $this->actorId($request),
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Authentication is required.']], 401);
        }

        return UserResource::make($user)->response();
    }
}
