<?php

namespace Tests\Feature\Cv;

use App\Models\User;
use App\Modules\Cv\Models\CvDraft;
use App\Modules\Cv\Models\CvExport;
use App\Modules\Cv\Rendering\CvPdfRenderer;
use App\Modules\Cv\Rendering\UnconfiguredPdfRenderer;
use App\Modules\Cv\Services\CvDraftService;
use App\Modules\Cv\Services\CvExportService;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CvExportGateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(CvExportService::DISK);

        // Gate tests check the rules, not the layout, so a fake renderer is bound for this test only.
        $this->app->instance(CvPdfRenderer::class, new class implements CvPdfRenderer
        {
            public function render(array $layout): string
            {
                return "%PDF-1.4\n%fake for gate tests\n%%EOF\n";
            }
        });
    }

    private function employeeOf(Company $company): User
    {
        $user = $this->makeUser();
        $this->makeMembership($company, $user, CompanyRole::Employee);

        return $user;
    }

    /**
     * Creates a candidate and a draft directly in the company context. Returns the draft ID.
     */
    private function draftIn(Company $company, User $actor): string
    {
        return (string) $this->inCompany($company, function () use ($company, $actor): string {
            $candidate = Candidate::query()->create([
                'full_name' => 'Export Candidate',
                'created_by' => $actor->id,
            ]);

            return (string) app(CvDraftService::class)
                ->create($candidate, 'classic', $actor->id, $company->id)
                ->id;
        });
    }

    public function test_unreviewed_draft_cannot_be_exported(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $draftId = $this->draftIn($company, $employee);

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}/export")
            ->assertStatus(422);

        $this->assertSame(0, (int) $this->inCompany($company, fn () => CvExport::query()->count()));
    }

    public function test_reviewed_draft_exports_and_is_audited(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $draftId = $this->draftIn($company, $employee);

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}/review")
            ->assertOk();

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}/export")
            ->assertCreated()
            ->assertJsonMissingPath('data.storage_key');

        $this->assertDatabaseHas('audit_logs', ['action' => 'cv_export.created', 'company_id' => $company->id]);
    }

    public function test_content_changed_after_review_is_refused_even_if_review_flag_remains(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $draftId = $this->draftIn($company, $employee);

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}/review")
            ->assertOk();

        // Simulate a change that bypasses the service: the review flag and hash stay as they were.
        $this->inCompany($company, function () use ($draftId): void {
            $draft = CvDraft::query()->whereKey($draftId)->firstOrFail();
            $content = $draft->content;
            $content['summary'] = 'Changed after review';
            $draft->forceFill(['content' => $content])->save();
        });

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}/export")
            ->assertStatus(422);

        $this->assertSame(0, (int) $this->inCompany($company, fn () => CvExport::query()->count()));
    }

    public function test_export_of_another_companys_draft_is_not_found(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $employeeB = $this->employeeOf($companyB);
        $draftB = $this->draftIn($companyB, $employeeB);

        $this->actingAs($employeeA)->frontend()
            ->postJson("/api/v1/companies/{$companyA->id}/cv-drafts/{$draftB}/export")
            ->assertNotFound();
    }

    public function test_default_renderer_refuses_to_produce_output(): void
    {
        // Remove the fake for this one test, to check the production default.
        $this->app->bind(CvPdfRenderer::class, UnconfiguredPdfRenderer::class);

        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $draftId = $this->draftIn($company, $employee);

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}/review")
            ->assertOk();

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}/export")
            ->assertStatus(500);

        $this->assertSame([], Storage::disk(CvExportService::DISK)->allFiles());
    }
}
