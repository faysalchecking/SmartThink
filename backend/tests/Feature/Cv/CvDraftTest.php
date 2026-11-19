<?php

namespace Tests\Feature\Cv;

use App\Models\User;
use App\Modules\Cv\Models\CvDraft;
use App\Modules\Cv\Services\CvDraftService;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CvDraftTest extends TestCase
{
    use DatabaseTransactions;

    private function employeeOf(Company $company): User
    {
        $user = $this->makeUser();
        $this->makeMembership($company, $user, CompanyRole::Employee);

        return $user;
    }

    private function candidateId(Company $company, User $actor): string
    {
        return (string) $this->actingAs($actor)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/candidates", [
                'full_name' => 'Ayesha Rahman',
                'email' => 'ayesha@example.test',
                'phone' => '+8801700000000',
                'nationality' => 'Bangladeshi',
                'date_of_birth' => '1995-04-12',
                'summary' => 'Hotel receptionist.',
                'passport_number' => 'AB1234567',
            ])
            ->assertCreated()
            ->json('data.id');
    }

    private function draftId(Company $company, User $actor, string $candidateId): string
    {
        return (string) $this->actingAs($actor)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/candidates/{$candidateId}/cv-drafts", ['template_key' => 'classic'])
            ->assertCreated()
            ->json('data.id');
    }

    /**
     * @return array<string, mixed>
     */
    private function fullContent(): array
    {
        return [
            'personal' => [
                'full_name' => 'Ayesha Rahman',
                'email' => 'ayesha@example.test',
                'phone' => '+8801700000000',
                'nationality' => 'Bangladeshi',
                'date_of_birth' => '1995-04-12',
            ],
            'summary' => 'Experienced hotel receptionist.',
            'experience' => [[
                'title' => 'Receptionist',
                'employer' => 'Hotel Example',
                'country' => 'SA',
                'start' => '2019-01',
                'end' => '2023-12',
                'description' => 'Front desk operations.',
            ]],
            'education' => [['institution' => 'Dhaka College', 'qualification' => 'HSC', 'year' => '2014']],
            'skills' => ['Customer service', 'Reservations'],
            'languages' => [['name' => 'English', 'level' => 'Good']],
        ];
    }

    /**
     * @param  array<int, mixed>  $sections
     * @return array<string, mixed>
     */
    private function sectionOf(array $sections, string $key): array
    {
        foreach ($sections as $section) {
            if (is_array($section) && ($section['key'] ?? null) === $key) {
                return $section;
            }
        }

        return [];
    }

    public function test_seeded_draft_copies_candidate_fields_but_never_the_passport(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateId($company, $employee);

        $response = $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/candidates/{$candidateId}/cv-drafts", ['template_key' => 'modern'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.template_key', 'modern')
            ->assertJsonPath('data.content.personal.full_name', 'Ayesha Rahman')
            ->assertJsonPath('data.reviewed', false);

        $this->assertStringNotContainsString('AB1234567', $response->getContent());
    }

    public function test_preview_marks_missing_entries_and_does_not_invent_them(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateId($company, $employee);
        $draftId = $this->draftId($company, $employee, $candidateId);

        $sections = (array) $this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}/preview")
            ->assertOk()
            ->json('data.sections');

        $experience = $this->sectionOf($sections, 'experience');
        $this->assertSame([], $experience['items']);
        $this->assertContains('entries', $experience['missing']);
    }

    public function test_editing_the_draft_does_not_change_the_candidate_record(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateId($company, $employee);
        $draftId = $this->draftId($company, $employee, $candidateId);

        $content = $this->fullContent();
        $content['personal']['full_name'] = 'Ayesha R.';

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}", ['content' => $content])
            ->assertOk()
            ->assertJsonPath('data.content.personal.full_name', 'Ayesha R.');

        $this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}/candidates/{$candidateId}")
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Ayesha Rahman');
    }

    public function test_optional_section_can_be_hidden_but_personal_cannot(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateId($company, $employee);
        $draftId = $this->draftId($company, $employee, $candidateId);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}", ['section_visibility' => ['experience' => false]])
            ->assertOk();

        $sections = (array) $this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}/preview")
            ->json('data.sections');

        $this->assertFalse($this->sectionOf($sections, 'experience')['visible']);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}", ['section_visibility' => ['personal' => false]])
            ->assertStatus(422);
    }

    public function test_switching_template_keeps_the_content(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateId($company, $employee);
        $draftId = $this->draftId($company, $employee, $candidateId);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}", ['template_key' => 'compact'])
            ->assertOk();

        $this->actingAs($employee)->frontend()
            ->getJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}")
            ->assertOk()
            ->assertJsonPath('data.template_key', 'compact')
            ->assertJsonPath('data.content.personal.full_name', 'Ayesha Rahman');
    }

    public function test_review_is_fingerprinted_and_any_edit_clears_it(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateId($company, $employee);
        $draftId = $this->draftId($company, $employee, $candidateId);

        $this->actingAs($employee)->frontend()
            ->postJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}/review")
            ->assertOk()
            ->assertJsonPath('data.status', 'reviewed')
            ->assertJsonPath('data.reviewed', true);

        $hash = $this->inCompany($company, fn () => CvDraft::query()->whereKey($draftId)->value('content_hash'));
        $this->assertIsString($hash);
        $this->assertSame(64, strlen($hash));

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}", ['template_key' => 'modern'])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.reviewed', false);

        $clearedHash = $this->inCompany($company, fn () => CvDraft::query()->whereKey($draftId)->value('content_hash'));
        $this->assertNull($clearedHash);
    }

    public function test_section_order_must_list_each_section_exactly_once(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateId($company, $employee);
        $draftId = $this->draftId($company, $employee, $candidateId);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}", [
                'section_order' => ['personal', 'summary', 'experience', 'education', 'skills', 'skills'],
            ])
            ->assertStatus(422);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}", [
                'section_order' => ['personal', 'summary'],
            ])
            ->assertStatus(422);
    }

    public function test_unknown_section_and_too_many_entries_are_rejected(): void
    {
        $company = $this->makeCompany();
        $employee = $this->employeeOf($company);
        $candidateId = $this->candidateId($company, $employee);
        $draftId = $this->draftId($company, $employee, $candidateId);

        $withExtra = $this->fullContent();
        $withExtra['hobbies'] = ['chess'];

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}", ['content' => $withExtra])
            ->assertStatus(422);

        $tooMany = $this->fullContent();
        $tooMany['experience'] = array_fill(0, 21, ['title' => 'Role']);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$company->id}/cv-drafts/{$draftId}", ['content' => $tooMany])
            ->assertStatus(422);
    }

    public function test_draft_of_another_company_is_not_found(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employeeA = $this->employeeOf($companyA);
        $employeeB = $this->employeeOf($companyB);

        $draftB = $this->inCompany($companyB, function () use ($companyB, $employeeB): string {
            $candidate = Candidate::query()->create([
                'full_name' => 'B Candidate',
                'created_by' => $employeeB->id,
            ]);

            return (string) app(CvDraftService::class)
                ->create($candidate, 'classic', $employeeB->id, $companyB->id)
                ->id;
        });

        $this->actingAs($employeeA)->frontend()
            ->getJson("/api/v1/companies/{$companyA->id}/cv-drafts/{$draftB}")
            ->assertNotFound();

        $this->actingAs($employeeA)->frontend()
            ->patchJson("/api/v1/companies/{$companyA->id}/cv-drafts/{$draftB}", ['template_key' => 'modern'])
            ->assertNotFound();
    }

    public function test_company_id_in_body_cannot_move_a_draft(): void
    {
        $companyA = $this->makeCompany(name: 'A');
        $companyB = $this->makeCompany(name: 'B');
        $employee = $this->employeeOf($companyA);
        $candidateId = $this->candidateId($companyA, $employee);
        $draftId = $this->draftId($companyA, $employee, $candidateId);

        $this->actingAs($employee)->frontend()
            ->patchJson("/api/v1/companies/{$companyA->id}/cv-drafts/{$draftId}", [
                'template_key' => 'compact',
                'company_id' => $companyB->id,
            ])
            ->assertOk();

        $owner = $this->inCompany($companyA, fn () => CvDraft::query()->whereKey($draftId)->value('company_id'));
        $this->assertSame($companyA->id, $owner);
    }
}
