<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PasswordService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Sets a new password, rotates the current session ID, and deletes every other session
     * of this user. The password is hashed by the model cast.
     */
    public function change(User $user, string $newPassword, Request $request): void
    {
        DB::transaction(function () use ($user, $newPassword): void {
            $user->forceFill(['password' => $newPassword])->save();

            $this->audit->record(
                action: 'auth.password_changed',
                result: 'success',
                targetType: 'user',
                targetId: (string) $user->id,
                actorId: $user->id,
            );
        });

        // Rotate the session ID so a stolen pre-change session ID is useless.
        $request->session()->regenerate();
        $currentSessionId = $request->session()->getId();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }
}
