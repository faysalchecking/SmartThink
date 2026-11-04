<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recruitment\Http\Requests\JobOrderRequest;
use App\Modules\Recruitment\Http\Resources\JobOrderResource;
use App\Modules\Recruitment\Models\JobOrder;
use App\Modules\Recruitment\Services\RecruitmentService;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class JobOrderController extends Controller
{
    public function __construct(private readonly RecruitmentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        $query = JobOrder::query()->orderByDesc('created_at')->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        return JobOrderResource::collection($query->paginate($perPage))->response();
    }

    public function store(JobOrderRequest $request): JsonResponse
    {
        $companyId = ResolveCompany::membership($request)->company_id;

        $job = $this->service->createJob($request->validated(), $this->actorId($request), $companyId);

        return JobOrderResource::make($job)->response()->setStatusCode(201);
    }

    public function show(Request $request): JsonResponse
    {
        return JobOrderResource::make($this->findJob($request))->response();
    }

    public function update(JobOrderRequest $request): JsonResponse
    {
        $job = $this->findJob($request);
        $companyId = ResolveCompany::membership($request)->company_id;

        $updated = $this->service->updateJob($job, $request->validated(), $this->actorId($request), $companyId);

        return JobOrderResource::make($updated)->response();
    }

    /**
     * Reads the job ID by route parameter name. Argument order is not used, because
     * the {company} parameter comes first in the route and would otherwise be picked up.
     */
    private function findJob(Request $request): JobOrder
    {
        $id = (string) $request->route('job');

        if (! Str::isUuid($id)) {
            abort(404);
        }

        return JobOrder::query()->findOrFail($id);
    }
}
