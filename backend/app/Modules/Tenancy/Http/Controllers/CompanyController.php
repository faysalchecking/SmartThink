<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use App\Modules\Tenancy\Http\Requests\UpdateCompanySettingsRequest;
use App\Modules\Tenancy\Http\Resources\CompanyResource;
use App\Modules\Tenancy\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CompanyController extends Controller
{
    public function __construct(private readonly CompanyService $companies) {}

    public function show(Request $request): JsonResponse
    {
        $membership = ResolveCompany::membership($request);

        return CompanyResource::make($membership->company)->response();
    }

    public function update(UpdateCompanySettingsRequest $request): JsonResponse
    {
        $membership = ResolveCompany::membership($request);
        $data = $request->validated();

        $company = $this->companies->rename(
            company: $membership->company,
            name: (string) $data['name'],
            actorId: $this->actorId($request),
        );

        return CompanyResource::make($company)->response();
    }
}
