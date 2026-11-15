<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recruitment\Services\DashboardService;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function show(Request $request): JsonResponse
    {
        $companyId = ResolveCompany::membership($request)->company_id;

        return response()->json(['data' => $this->dashboard->summary($companyId)]);
    }
}
