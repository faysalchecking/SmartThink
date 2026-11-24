<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_health_response_carries_security_headers(): void
    {
        $response = $this->getJson('/api/v1/health')->assertOk();

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame("default-src 'none'; frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_error_response_also_carries_security_headers(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist')->assertNotFound();

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }
}
