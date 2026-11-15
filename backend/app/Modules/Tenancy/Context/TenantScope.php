<?php

namespace App\Modules\Tenancy\Context;

/**
 * Runs work for one company in both layers (application scope and RLS), then clears both.
 * Use it for system work that has no request context, such as seeding a new company.
 * Do not nest it inside a company request, because it clears the context when it finishes.
 */
final class TenantScope
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly TenantDatabaseContext $database,
    ) {}

    public function run(string $companyId, callable $callback): mixed
    {
        $this->tenant->set($companyId);
        $this->database->apply($companyId);

        try {
            return $callback();
        } finally {
            $this->tenant->clear();
            $this->database->clear();
        }
    }
}
