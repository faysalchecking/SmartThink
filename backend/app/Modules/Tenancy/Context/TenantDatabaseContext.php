<?php

namespace App\Modules\Tenancy\Context;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Sets the company for the PostgreSQL connection, which RLS policies read.
 * The value is a session setting, so it must always be cleared when the work ends.
 */
final class TenantDatabaseContext
{
    public function apply(string $companyId): void
    {
        if (! Str::isUuid($companyId)) {
            throw new InvalidArgumentException('Company context must be a UUID.');
        }

        DB::connection('pgsql')->select("SELECT set_config('app.company_id', ?, false)", [$companyId]);
    }

    public function clear(): void
    {
        DB::connection('pgsql')->select("SELECT set_config('app.company_id', '', false)");
    }
}
