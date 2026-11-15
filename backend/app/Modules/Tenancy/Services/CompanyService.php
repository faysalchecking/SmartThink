<?php

namespace App\Modules\Tenancy\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Recruitment\Services\PipelineStageSeeder;
use App\Modules\Tenancy\Enums\CompanyStatus;
use App\Modules\Tenancy\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompanyService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PipelineStageSeeder $stages,
    ) {}

    public function create(string $name, string $slug, int $actorId): Company
    {
        return DB::transaction(function () use ($name, $slug, $actorId): Company {
            $company = Company::query()->create([
                'name' => $name,
                'slug' => $slug,
                'status' => CompanyStatus::Pending,
            ]);

            $this->audit->record(
                action: 'company.created',
                result: 'success',
                targetType: 'company',
                targetId: $company->id,
                actorId: $actorId,
                companyId: $company->id,
                metadata: ['slug' => $slug],
            );

            $this->stages->seed($company->id);

            return $company;
        });
    }

    public function rename(Company $company, string $name, int $actorId): Company
    {
        return DB::transaction(function () use ($company, $name, $actorId): Company {
            $company->update(['name' => $name]);

            $this->audit->record(
                action: 'company.updated',
                result: 'success',
                targetType: 'company',
                targetId: $company->id,
                actorId: $actorId,
                companyId: $company->id,
                metadata: ['fields' => ['name']],
            );

            return $company;
        });
    }

    public function changeStatus(Company $company, CompanyStatus $target, int $actorId): Company
    {
        if (! $company->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => 'This status change is not allowed from the current status.',
            ]);
        }

        return DB::transaction(function () use ($company, $target, $actorId): Company {
            $previous = $company->status->value;

            $company->update(['status' => $target]);

            $this->audit->record(
                action: 'company.status_changed',
                result: 'success',
                targetType: 'company',
                targetId: $company->id,
                actorId: $actorId,
                companyId: $company->id,
                metadata: ['from' => $previous, 'to' => $target->value],
            );

            return $company;
        });
    }
}
