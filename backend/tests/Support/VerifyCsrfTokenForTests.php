<?php

namespace Tests\Support;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

/**
 * Same CSRF check as production. The only change: Laravel skips CSRF while unit tests run,
 * and this subclass turns that skip off so the real check is exercised.
 */
final class VerifyCsrfTokenForTests extends VerifyCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
