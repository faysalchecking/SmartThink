<?php

namespace App\Modules\Cv\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cv\Http\Requests\CvDraftRequest;
use App\Modules\Cv\Http\Resources\CvDraftResource;
use App\Modules\Cv\Models\CvDraft;
use App\Modules\Cv\Services\CvDraftService;
use App\Modules\Cv\Services\CvExportService;
use App\Modules\Cv\Support\CvLayout;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CvDraftController extends Controller
{
    public function __construct(
        private readonly CvDraftService $drafts,
        private readonly CvExportService $exports,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $candidate = $this->candidate($request);
        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        $rows = CvDraft::query()
            ->where('candidate_id', $candidate->id)
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->paginate($perPage);

        return CvDraftResource::collection($rows)->response();
    }

    public function store(CvDraftRequest $request): JsonResponse
    {
        $candidate = $this->candidate($request);
        $companyId = ResolveCompany::membership($request)->company_id;
        $template = (string) ($request->validated()['template_key'] ?? 'classic');

        $draft = $this->drafts->create($candidate, $template, $this->actorId($request), $companyId);

        return CvDraftResource::make($draft)->response()->setStatusCode(201);
    }

    public function show(Request $request): JsonResponse
    {
        return CvDraftResource::make($this->draft($request))->response();
    }

    public function update(CvDraftRequest $request): JsonResponse
    {
        $draft = $this->draft($request);
        $companyId = ResolveCompany::membership($request)->company_id;

        $updated = $this->drafts->update($draft, $request->validated(), $this->actorId($request), $companyId);

        return CvDraftResource::make($updated)->response();
    }

    public function review(Request $request): JsonResponse
    {
        $draft = $this->draft($request);
        $companyId = ResolveCompany::membership($request)->company_id;

        $reviewed = $this->drafts->review($draft, $this->actorId($request), $companyId);

        return CvDraftResource::make($reviewed)->response();
    }

    public function preview(Request $request): JsonResponse
    {
        return response()->json(['data' => CvLayout::build($this->draft($request))]);
    }

    public function export(Request $request): JsonResponse
    {
        $draft = $this->draft($request);
        $companyId = ResolveCompany::membership($request)->company_id;

        $export = $this->exports->export($draft, $this->actorId($request), $companyId);

        return response()->json(['data' => [
            'id' => $export->id,
            'cv_draft_id' => $export->cv_draft_id,
            'size_bytes' => $export->size_bytes,
        ]], 201);
    }

    private function candidate(Request $request): Candidate
    {
        return Candidate::query()->findOrFail($this->routeUuid($request, 'candidate'));
    }

    private function draft(Request $request): CvDraft
    {
        return CvDraft::query()->findOrFail($this->routeUuid($request, 'draft'));
    }
}
