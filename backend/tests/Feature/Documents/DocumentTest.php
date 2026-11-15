<?php

namespace Tests\Feature\Documents;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentService;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use DatabaseTransactions;

    private const VALID_PDF = "%PDF-1.4\n1 0 obj\n<< >>\nendobj\n%%EOF\n";

    protected function setUp(): void
    {
        parent::setUp();

        // Files go to a fake disk, so tests never touch the real storage folder.
        Storage::fake(DocumentService::DISK);
    }

    private function employeeOf(Company $company): User
    {
        $user = $this->makeUser();
        $this->makeMembership($company, $user, CompanyRole::Employee);

        return $user;
    }

    private function candidateIn(Company $company, User $actor): string
    {
        return (string) $this->inCompany($company, fn () => Candidate::query()->create([
            'full_name' => 'Document Candidate',
            'created_by' => $actor->id,
        ])->id);
    }

    /**
     * Creates a document row directly in the company context, without an HTTP request.
     * Used by cross-company tests, so each test makes only one authenticated request.
     */
    private function documentIn(Company $company, User $actor, string $candidateId): string
    {
        return (string) $this->inCompany($company, function () use ($actor, $candidateId): string {
            $document = new Document;
            $document->forceFill([
                'candidate_id' => $candidateId,
                'document_type' => 'passport',
                'storage_key' => (string) Str::uuid(),
                'original_name' => 'b.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => 10,
                'sha256' => str_repeat('a', 64),
                'review_status' => 'pending',
                'uploaded_by' => $actor->id,
            ]);
            $document->save();

            return (string) $document->id;
        });
    }

    /**
     * Uploads a file and returns the new document ID. Fails the test on any non-201 response.
     */
    private function upload(Company $company, User $user, string $candidateId, UploadedFile $file, string $type = 'passport'): string
    {
        return (string) $this->actingAs($user)->frontend()
            ->post("/api/v1/companies/{$company->id}/candidates/{$candidateId}/documents", [
                'file' => $file,
                'document_type' => $type,
            ])
            ->assertCreated()
            ->json('data.id');
    }

    public function test_valid_pdf_is_stored_privately_and_the_key_is_not_exposed(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateIn($company, $employee);

        $response = $this->actingAs($employee)->frontend()
            ->post("/api/v1/companies/{$company->id}/candidates/{$candidateId}/documents", [
                'file' => UploadedFile::fake()->createWithContent('passport.pdf', self::VALID_PDF),
                'document_type' => 'passport',
            ])
            ->assertCreated()
            ->assertJsonPath('data.mime_type', 'application/pdf')
            ->assertJsonPath('data.review_status', 'pending');

        $this->assertStringNotContainsString('storage_key', $response->getContent());

        $documentId = (string) $response->json('data.id');
        $key = (string) $this->inCompany($company, fn () => Document::query()->whereKey($documentId)->value('storage_key'));
        Storage::disk(DocumentService::DISK)->assertExists('documents/'.$company->id.'/'.$key);
    }

    public function test_disguised_file_is_rejected_and_nothing_is_stored(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateIn($company, $employee);

        // Plain text with a .pdf name: wrong content, so it must fail.
        $this->actingAs($employee)->frontend()
            ->post("/api/v1/companies/{$company->id}/candidates/{$candidateId}/documents", [
                'file' => UploadedFile::fake()->createWithContent('passport.pdf', "<?php echo 'x';"),
                'document_type' => 'passport',
            ])
            ->assertStatus(422);

        $this->assertSame([], Storage::disk(DocumentService::DISK)->allFiles());
    }

    public function test_oversized_file_is_rejected(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateIn($company, $employee);

        $this->actingAs($employee)->frontend()
            ->post("/api/v1/companies/{$company->id}/candidates/{$candidateId}/documents", [
                'file' => UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf'),
                'document_type' => 'passport',
            ])
            ->assertStatus(422);
    }

    public function test_unknown_document_type_is_rejected(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateIn($company, $employee);

        $this->actingAs($employee)->frontend()
            ->post("/api/v1/companies/{$company->id}/candidates/{$candidateId}/documents", [
                'file' => UploadedFile::fake()->createWithContent('a.pdf', self::VALID_PDF),
                'document_type' => 'diploma',
            ])
            ->assertStatus(422);
    }

    public function test_download_is_an_attachment_with_nosniff_and_is_audited(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateIn($company, $employee);
        $documentId = $this->upload($company, $employee, $candidateId, UploadedFile::fake()->createWithContent('passport.pdf', self::VALID_PDF));

        $response = $this->actingAs($employee)->frontend()
            ->get("/api/v1/companies/{$company->id}/documents/{$documentId}/download")
            ->assertOk();

        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.downloaded', 'target_id' => $documentId]);
    }

    public function test_document_of_another_company_cannot_be_downloaded(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $employeeB = $this->employeeOf($companyB);
        $candidateB = $this->candidateIn($companyB, $employeeB);
        $documentB = $this->documentIn($companyB, $employeeB, $candidateB);

        $this->actingAs($employeeA)->frontend()
            ->get("/api/v1/companies/{$companyA->id}/documents/{$documentB}/download")
            ->assertNotFound();
    }

    public function test_document_of_another_company_cannot_be_archived(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $employeeB = $this->employeeOf($companyB);
        $candidateB = $this->candidateIn($companyB, $employeeB);
        $documentB = $this->documentIn($companyB, $employeeB, $candidateB);

        $this->actingAs($employeeA)->frontend()
            ->postJson("/api/v1/companies/{$companyA->id}/documents/{$documentB}/archive")
            ->assertNotFound();

        $archived = $this->inCompany($companyB, fn () => Document::query()->whereKey($documentB)->value('archived_at'));
        $this->assertNull($archived);
    }

    public function test_candidate_of_another_company_cannot_receive_a_document(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $employeeB = $this->employeeOf($companyB);
        $candidateB = $this->candidateIn($companyB, $employeeB);

        $this->actingAs($employeeA)->frontend()
            ->post("/api/v1/companies/{$companyA->id}/candidates/{$candidateB}/documents", [
                'file' => UploadedFile::fake()->createWithContent('x.pdf', self::VALID_PDF),
                'document_type' => 'passport',
            ])
            ->assertNotFound();
    }

    public function test_rejection_requires_a_reason_and_verification_is_audited(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateIn($company, $employee);
        $documentId = $this->upload($company, $employee, $candidateId, UploadedFile::fake()->createWithContent('p.pdf', self::VALID_PDF));

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/documents/{$documentId}/review", ['status' => 'rejected'])
            ->assertStatus(422);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/documents/{$documentId}/review", ['status' => 'verified'])
            ->assertOk()
            ->assertJsonPath('data.review_status', 'verified');

        $this->assertDatabaseHas('audit_logs', ['action' => 'document.reviewed', 'target_id' => $documentId]);
    }

    public function test_archive_hides_the_document_but_keeps_the_file(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateIn($company, $employee);
        $documentId = $this->upload($company, $employee, $candidateId, UploadedFile::fake()->createWithContent('p.pdf', self::VALID_PDF));

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/documents/{$documentId}/archive")
            ->assertOk()
            ->assertJsonPath('data.archived', true);

        $listed = $this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}/candidates/{$candidateId}/documents")
            ->json('data');
        $this->assertSame([], $listed);

        $key = (string) $this->inCompany($company, fn () => Document::query()->whereKey($documentId)->value('storage_key'));
        Storage::disk(DocumentService::DISK)->assertExists('documents/'.$company->id.'/'.$key);
    }

    public function test_original_name_is_sanitised(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateIn($company, $employee);

        $documentId = $this->upload(
            $company,
            $employee,
            $candidateId,
            UploadedFile::fake()->createWithContent('../../evil<script>.pdf', self::VALID_PDF),
        );

        $name = (string) $this->inCompany($company, fn () => Document::query()->whereKey($documentId)->value('original_name'));
        $this->assertStringNotContainsString('..', $name);
        $this->assertStringNotContainsString('/', $name);
        $this->assertStringNotContainsString('<', $name);
    }
}
