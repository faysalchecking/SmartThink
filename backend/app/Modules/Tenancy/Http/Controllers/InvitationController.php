<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use App\Modules\Tenancy\Http\Requests\AcceptInvitationRequest;
use App\Modules\Tenancy\Http\Requests\InviteMemberRequest;
use App\Modules\Tenancy\Http\Resources\MembershipResource;
use App\Modules\Tenancy\Services\InvitationService;
use Illuminate\Http\JsonResponse;

final class InvitationController extends Controller
{
    public function __construct(private readonly InvitationService $invitations) {}

    public function store(InviteMemberRequest $request): JsonResponse
    {
        $company = ResolveCompany::membership($request)->company;
        $data = $request->validated();

        $token = $this->invitations->create(
            company: $company,
            email: (string) $data['email'],
            role: CompanyRole::from((string) $data['role']),
            actorId: $this->actorId($request),
        );

        // Shown once to the admin who created it. The database keeps only the hash.
        return response()->json([
            'data' => [
                'invitation_token' => $token,
                'expires_in_days' => InvitationService::VALID_DAYS,
            ],
        ], 201);
    }

    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $membership = $this->invitations->accept(
            rawToken: (string) $data['token'],
            signedIn: $user instanceof User ? $user : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            password: isset($data['password']) ? (string) $data['password'] : null,
        );

        return MembershipResource::make($membership)->response()->setStatusCode(201);
    }
}
