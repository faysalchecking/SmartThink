<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recruitment\Http\Requests\ClientRequest;
use App\Modules\Recruitment\Http\Resources\ClientResource;
use App\Modules\Recruitment\Models\Client;
use App\Modules\Recruitment\Services\RecruitmentService;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ClientController extends Controller
{
    public function __construct(private readonly RecruitmentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        $clients = Client::query()
            ->whereNull('archived_at')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);

        return ClientResource::collection($clients)->response();
    }

    public function store(ClientRequest $request): JsonResponse
    {
        $companyId = ResolveCompany::membership($request)->company_id;

        $client = $this->service->createClient($request->validated(), $this->actorId($request), $companyId);

        return ClientResource::make($client)->response()->setStatusCode(201);
    }

    public function show(Request $request): JsonResponse
    {
        return ClientResource::make($this->findClient($request))->response();
    }

    public function update(ClientRequest $request): JsonResponse
    {
        $client = $this->findClient($request);
        $client->update($request->validated());

        return ClientResource::make($client)->response();
    }

    private function findClient(Request $request): Client
    {
        $id = (string) $request->route('client');

        if (! Str::isUuid($id)) {
            abort(404);
        }

        return Client::query()->findOrFail($id);
    }
}
