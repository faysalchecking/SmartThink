<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tenancy\Enums\CompanyStatus;
use App\Modules\Tenancy\Http\Requests\PlatformCreateCompanyRequest;
use App\Modules\Tenancy\Http\Requests\PlatformUpdateCompanyStatusRequest;
use App\Modules\Tenancy\Http\Resources\CompanyResource;
use App\Modules\Tenancy\Models\Company;
use App\Modules\Tenancy\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlatformCompanyController extends Controller
{
    public function __construct(private readonly CompanyService $companies) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        $companies = Company::query()
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);

        return CompanyResource::collection($companies)->response();
    }

    public function store(PlatformCreateCompanyRequest $request): JsonResponse
    {
        $data = $request->validated();

        $company = $this->companies->create(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            actorId: $this->actorId($request),
        );

        return CompanyResource::make($company)->response()->setStatusCode(201);
    }

    public function updateStatus(PlatformUpdateCompanyStatusRequest $request, Company $company): JsonResponse
    {
        $target = CompanyStatus::from((string) $request->validated('status'));

        $updated = $this->companies->changeStatus($company, $target, $this->actorId($request));

        return CompanyResource::make($updated)->response();
    }
}
