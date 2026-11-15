<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recruitment\Http\Requests\PipelineStageRequest;
use App\Modules\Recruitment\Http\Resources\PipelineStageResource;
use App\Modules\Recruitment\Models\PipelineStage;
use App\Modules\Recruitment\Services\PipelineService;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Http\JsonResponse;

final class PipelineStageController extends Controller
{
    public function __construct(private readonly PipelineService $pipeline) {}

    public function index(): JsonResponse
    {
        $stages = PipelineStage::query()->orderBy('position')->get();

        return PipelineStageResource::collection($stages)->response();
    }

    public function store(PipelineStageRequest $request): JsonResponse
    {
        $companyId = ResolveCompany::membership($request)->company_id;

        $stage = $this->pipeline->createStage($request->validated(), $this->actorId($request), $companyId);

        return PipelineStageResource::make($stage)->response()->setStatusCode(201);
    }

    public function update(PipelineStageRequest $request): JsonResponse
    {
        $stage = PipelineStage::query()->findOrFail($this->routeUuid($request, 'stage'));
        $companyId = ResolveCompany::membership($request)->company_id;

        $updated = $this->pipeline->updateStage($stage, $request->validated(), $this->actorId($request), $companyId);

        return PipelineStageResource::make($updated)->response();
    }
}
