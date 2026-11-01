<?php

namespace App\Modules\Tenancy\Http\Resources;

use App\Modules\Tenancy\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MembershipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Membership $membership */
        $membership = $this->resource;
        $user = $membership->user;

        return [
            'id' => $membership->id,
            'company_id' => $membership->company_id,
            'role' => $membership->role->value,
            'status' => $membership->status->value,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }
}
