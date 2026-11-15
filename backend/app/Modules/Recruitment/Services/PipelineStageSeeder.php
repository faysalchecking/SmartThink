<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Recruitment\Models\PipelineStage;
use App\Modules\Tenancy\Context\TenantScope;

/**
 * Creates the default stages for a new company. Overseas stages are included but can be disabled
 * by the company later, so a domestic recruitment agency is not forced through them (D-32).
 */
final class PipelineStageSeeder
{
    /** @var list<array{0: string, 1: string, 2: bool}> name, kind, is_terminal */
    public const DEFAULT_STAGES = [
        ['Screening', 'standard', false],
        ['Interview', 'standard', false],
        ['Selected', 'standard', false],
        ['Passport Check', 'overseas', false],
        ['Medical', 'overseas', false],
        ['Visa', 'overseas', false],
        ['Embassy', 'overseas', false],
        ['Deployment', 'overseas', true],
    ];

    public function __construct(private readonly TenantScope $scope) {}

    public function seed(string $companyId): void
    {
        $this->scope->run($companyId, function (): void {
            foreach (self::DEFAULT_STAGES as $index => [$name, $kind, $terminal]) {
                PipelineStage::query()->create([
                    'name' => $name,
                    'position' => $index + 1,
                    'kind' => $kind,
                    'is_terminal' => $terminal,
                    'enabled' => true,
                ]);
            }
        });
    }
}
