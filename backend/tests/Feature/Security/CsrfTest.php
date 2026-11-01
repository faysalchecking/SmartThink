<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\VerifyCsrfTokenForTests;
use Tests\TestCase;

class CsrfTest extends TestCase
{
    use DatabaseTransactions;

    // Keep the CSRF check active for this class only.
    protected bool $keepCsrf = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum reads this key when it builds the stateful middleware stack.
        config(['sanctum.middleware.validate_csrf_token' => VerifyCsrfTokenForTests::class]);
    }

    public function test_logout_without_csrf_token_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->frontend()
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(419);
    }

    public function test_login_without_csrf_token_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->frontend()
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => self::TEST_PASSWORD])
            ->assertStatus(419);
    }
}
