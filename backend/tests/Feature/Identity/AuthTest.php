<?php

namespace Tests\Feature\Identity;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_with_valid_credentials_returns_user_without_password(): void
    {
        $user = $this->makeUser();

        $this->frontend()
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => self::TEST_PASSWORD])
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_wrong_password_and_unknown_email_return_the_same_error(): void
    {
        $user = $this->makeUser();

        $wrongPassword = $this->frontend()
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong-password-value'])
            ->assertStatus(401)
            ->json();

        $unknownEmail = $this->frontend()
            ->postJson('/api/v1/auth/login', ['email' => 'nobody@example.test', 'password' => 'wrong-password-value'])
            ->assertStatus(401)
            ->json();

        $this->assertSame($wrongPassword, $unknownEmail);
        $this->assertSame('INVALID_CREDENTIALS', $wrongPassword['error']['code']);
    }

    public function test_disabled_user_cannot_log_in(): void
    {
        $user = $this->makeUser(status: 'disabled');

        $this->frontend()
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => self::TEST_PASSWORD])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->frontend()
                ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong-password-value'])
                ->assertStatus(401);
        }

        $this->frontend()
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong-password-value'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'RATE_LIMITED');
    }

    public function test_me_requires_authentication(): void
    {
        $this->frontend()
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_me_returns_the_signed_in_user(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->frontend()
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_logout_returns_no_content(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->frontend()
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();
    }
}
