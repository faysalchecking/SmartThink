<?php

namespace App\Modules\Tenancy\Context;

final class TenantContext
{
    private ?string $companyId = null;

    public function set(string $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function companyId(): ?string
    {
        return $this->companyId;
    }

    public function clear(): void
    {
        $this->companyId = null;
    }
}
