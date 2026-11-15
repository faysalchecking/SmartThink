<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recruitment\Http\Requests\ApplicationRequest;
use App\Modules\Recruitment\Http\Requests\MoveStageRequest;
use App\Modules\Recruitment\Http\Resources\ApplicationResource;
use App\Modules\Recruitment\Http\Resources\StageHistoryResource;
use App\Modules\Recruitment\Models\CandidateApplication;
use App\Modules\Recruitment\Models\StageHistory;
use App\Modules\Recruitment\Services\PipelineService;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApplicationController extends Controller
{
    public function __construct(private readonly PipelineService $pipeline) {}

    public function store(ApplicationRequest $request): JsonResponse
    {
        $companyId = ResolveCompany::membership($request)->company_id;

        $application = $this->pipeline->startApplication($request->validated(), $this->actorId($request), $companyId);

        return ApplicationResource::make($application)->response()->setStatusCode(201);
    }

    public function moveStage(MoveStageRequest $request): JsonResponse
    {
        $application = $this->findApplication($request);
        $companyId = ResolveCompany::membership($request)->company_id;
        $data = $request->validated();

        $updated = $this->pipeline->moveStage(
            $application,
            (string) $data['stage_id'],
            isset($data['note']) ? (string) $data['note'] : null,
            $this->actorId($request),
            $companyId,
        );

        return ApplicationResource::make($updated)->response();
    }

    public function history(Request $request): JsonResponse
    {
        $application = $this->findApplication($request);

        $rows = StageHistory::query()
            ->where('application_id', $application->id)
            ->orderBy('id')
            ->paginate(50);

        return StageHistoryResource::collection($rows)->response();
    }

    private function findApplication(Request $request): CandidateApplication
    {
        return CandidateApplication::query()->findOrFail($this->routeUuid($request, 'application'));
    }
}
