<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recruitment\Http\Requests\InterviewRequest;
use App\Modules\Recruitment\Http\Resources\InterviewResource;
use App\Modules\Recruitment\Models\Interview;
use App\Modules\Recruitment\Services\InterviewService;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InterviewController extends Controller
{
    public function __construct(private readonly InterviewService $interviews) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'application_id' => ['nullable', 'string', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Interview::query()->orderBy('scheduled_at')->orderBy('id');

        if (isset($filters['application_id'])) {
            $query->where('application_id', (string) $filters['application_id']);
        }

        return InterviewResource::collection($query->paginate((int) ($filters['per_page'] ?? 25)))->response();
    }

    public function store(InterviewRequest $request): JsonResponse
    {
        $companyId = ResolveCompany::membership($request)->company_id;

        $interview = $this->interviews->schedule($request->validated(), $this->actorId($request), $companyId);

        return InterviewResource::make($interview)->response()->setStatusCode(201);
    }

    public function update(InterviewRequest $request): JsonResponse
    {
        $interview = Interview::query()->findOrFail($this->routeUuid($request, 'interview'));
        $companyId = ResolveCompany::membership($request)->company_id;

        $updated = $this->interviews->update($interview, $request->validated(), $this->actorId($request), $companyId);

        return InterviewResource::make($updated)->response();
    }
}
