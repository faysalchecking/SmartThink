<?php

namespace App\Modules\Recruitment\Http\Resources;

use App\Modules\Recruitment\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Client $client */
        $client = $this->resource;

        return [
            'id' => $client->id,
            'name' => $client->name,
            'country' => $client->country,
            'contact_name' => $client->contact_name,
            'contact_email' => $client->contact_email,
            'archived' => $client->archived_at !== null,
        ];
    }
}
