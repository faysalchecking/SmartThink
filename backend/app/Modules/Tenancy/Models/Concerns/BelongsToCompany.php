<?php

namespace App\Modules\Tenancy\Models\Concerns;

use App\Modules\Tenancy\Context\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Tenant rules for application models (ADR-0003, layer 1):
 * - reads are limited to the current company; with no company, nothing matches;
 * - creates take company_id from the request context, never from input.
 */
trait BelongsToCompany
{
    private const NO_COMPANY = '00000000-0000-0000-0000-000000000000';

    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            $companyId = app(TenantContext::class)->companyId() ?? self::NO_COMPANY;
            $builder->where($builder->getModel()->getTable().'.company_id', $companyId);
        });

        static::creating(function (Model $model): void {
            $companyId = app(TenantContext::class)->companyId();

            if ($companyId === null) {
                throw new LogicException('A company context is required to create tenant data.');
            }

            $model->setAttribute('company_id', $companyId);
        });
    }
}
