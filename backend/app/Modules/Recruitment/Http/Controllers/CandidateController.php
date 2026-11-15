<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recruitment\Http\Requests\CandidateRequest;
use App\Modules\Recruitment\Http\Resources\CandidateResource;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Recruitment\Services\CandidateService;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CandidateController extends Controller
{
    public function __construct(private readonly CandidateService $candidates) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'include_archived' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Candidate::query()->orderBy('full_name')->orderBy('id');

        if (! ($filters['include_archived'] ?? false)) {
            $query->whereNull('archived_at');
        }

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            // Escape LIKE wildcards so the user's text is matched literally.
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(fn (Builder $inner) => $inner
                ->where('full_name', 'ilike', $like)
                ->orWhere('email', 'ilike', $like));
        }

        $perPage = (int) ($filters['per_page'] ?? 25);

        return CandidateResource::collection($query->paginate($perPage))->response();
    }

    public function store(CandidateRequest $request): JsonResponse
    {
        $companyId = ResolveCompany::membership($request)->company_id;

        $candidate = $this->candidates->create($request->validated(), $this->actorId($request), $companyId);

        return CandidateResource::make($candidate)->response()->setStatusCode(201);
    }

    public function show(Request $request): JsonResponse
    {
        return CandidateResource::make($this->findCandidate($request))->response();
    }

    public function update(CandidateRequest $request): JsonResponse
    {
        $candidate = $this->findCandidate($request);
        $companyId = ResolveCompany::membership($request)->company_id;

        $updated = $this->candidates->update($candidate, $request->validated(), $this->actorId($request), $companyId);

        return CandidateResource::make($updated)->response();
    }

    public function archive(Request $request): JsonResponse
    {
        $candidate = $this->findCandidate($request);
        $companyId = ResolveCompany::membership($request)->company_id;

        $archived = $this->candidates->archive($candidate, $this->actorId($request), $companyId);

        return CandidateResource::make($archived)->response();
    }

    private function findCandidate(Request $request): Candidate
    {
        return Candidate::query()->findOrFail($this->routeUuid($request, 'candidate'));
    }
}
