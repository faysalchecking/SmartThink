<?php

namespace App\Modules\Cv\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Cv\Models\CvDraft;
use App\Modules\Cv\Support\CvContent;
use App\Modules\Recruitment\Models\Candidate;
use Illuminate\Support\Facades\DB;

final class CvDraftService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(Candidate $candidate, string $template, int $actorId, string $companyId): CvDraft
    {
        return DB::transaction(function () use ($candidate, $template, $actorId, $companyId): CvDraft {
            $draft = new CvDraft;

            // forceFill: these values come from the server. company_id is set by the trait.
            $draft->forceFill([
                'candidate_id' => $candidate->id,
                'template_key' => $template,
                'content' => CvContent::seed($candidate),
                'section_order' => CvDraft::DEFAULT_ORDER,
                'section_visibility' => CvDraft::DEFAULT_VISIBILITY,
                'status' => 'draft',
                'created_by' => $actorId,
            ]);
            $draft->save();

            $this->audit->record('cv_draft.created', 'success', 'cv_draft', $draft->id, $actorId, $companyId);

            return $draft;
        });
    }

    /**
     * Any change sends the draft back to "draft" and clears the review.
     * The candidate record is never touched.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CvDraft $draft, array $data, int $actorId, string $companyId): CvDraft
    {
        return DB::transaction(function () use ($draft, $data, $actorId, $companyId): CvDraft {
            $changed = [];

            if (array_key_exists('template_key', $data)) {
                $draft->template_key = (string) $data['template_key'];
                $changed[] = 'template_key';
            }

            if (array_key_exists('content', $data)) {
                // Content is replaced as a whole. The client sends the full structure.
                $draft->content = CvContent::normalize((array) $data['content']);
                $changed[] = 'content';
            }

            if (array_key_exists('section_order', $data)) {
                $draft->section_order = array_values((array) $data['section_order']);
                $changed[] = 'section_order';
            }

            if (array_key_exists('section_visibility', $data)) {
                $incoming = [];
                foreach ((array) $data['section_visibility'] as $key => $value) {
                    $incoming[(string) $key] = (bool) $value;
                }

                // Merge into the existing visibility, so a partial update does not reset other sections.
                $draft->section_visibility = array_merge($draft->section_visibility, $incoming);
                $changed[] = 'section_visibility';
            }

            if ($changed === []) {
                return $draft;
            }

            $draft->status = 'draft';
            $draft->content_hash = null;
            $draft->reviewed_by = null;
            $draft->reviewed_at = null;
            $draft->save();

            $this->audit->record(
                'cv_draft.updated',
                'success',
                'cv_draft',
                $draft->id,
                $actorId,
                $companyId,
                ['fields' => $changed],
            );

            return $draft;
        });
    }

    public function review(CvDraft $draft, int $actorId, string $companyId): CvDraft
    {
        return DB::transaction(function () use ($draft, $actorId, $companyId): CvDraft {
            $draft->status = 'reviewed';
            $draft->content_hash = CvContent::fingerprintOf($draft);
            $draft->reviewed_by = $actorId;
            $draft->reviewed_at = now();
            $draft->save();

            $this->audit->record('cv_draft.reviewed', 'success', 'cv_draft', $draft->id, $actorId, $companyId);

            return $draft;
        });
    }
}
