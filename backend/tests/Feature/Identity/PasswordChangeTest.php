<?php

namespace Tests\Feature\Identity;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use DatabaseTransactions;

    private const NEW_PASSWORD = 'another-long-passphrase-2026';

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->frontend()
            ->patchJson('/api/v1/auth/password', [
                'current_password' => self::TEST_PASSWORD,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertStatus(401);
    }

    public function test_wrong_current_password_is_rejected_and_nothing_changes(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->frontend()
            ->patchJson('/api/v1/auth/password', [
                'current_password' => 'not-the-current-password',
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->frontend()
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => self::TEST_PASSWORD])
            ->assertOk();
    }

    public function test_short_new_password_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->frontend()
            ->patchJson('/api/v1/auth/password', [
                'current_password' => self::TEST_PASSWORD,
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertStatus(422);
    }

    public function test_new_password_replaces_the_old_one(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->frontend()
            ->patchJson('/api/v1/auth/password', [
                'current_password' => self::TEST_PASSWORD,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertNoContent();

        $this->frontend()
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => self::TEST_PASSWORD])
            ->assertStatus(401);

        $this->frontend()
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => self::NEW_PASSWORD])
            ->assertOk();
    }

    public function test_other_sessions_of_the_user_are_deleted(): void
    {
        $user = $this->makeUser();

        DB::table('sessions')->insert([
            'id' => 'other-session-'.$user->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-other-device',
            'payload' => 'x',
            'last_activity' => time(),
        ]);

        $this->actingAs($user)->frontend()
            ->patchJson('/api/v1/auth/password', [
                'current_password' => self::TEST_PASSWORD,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertNoContent();

        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-'.$user->id]);
    }

    public function test_password_change_is_audited_without_the_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->frontend()
            ->patchJson('/api/v1/auth/password', [
                'current_password' => self::TEST_PASSWORD,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password_changed',
            'actor_user_id' => $user->id,
            'result' => 'success',
        ]);

        $log = DB::table('audit_logs')->where('action', 'auth.password_changed')->latest('id')->first();
        $this->assertStringNotContainsString(self::NEW_PASSWORD, (string) $log->metadata);
        $this->assertStringNotContainsString(self::TEST_PASSWORD, (string) $log->metadata);
    }
}
